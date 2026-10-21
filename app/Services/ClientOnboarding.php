<?php

namespace App\Services;

use App\Enums\QuestionnaireStatus;
use App\Models\ClientQuestionnaire;
use App\Models\ClientQuestionnaireFile;
use App\Support\Onboarding\QuestionnaireForm;
use App\Support\Onboarding\QuestionnaireTemplate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The client onboarding workflow: saving and submitting answers, private
 * file uploads, reopening and link rotation. Every state change is audited.
 */
class ClientOnboarding
{
    public function __construct(protected AuditLogger $audit, protected InboxNotifier $notifier) {}

    /**
     * Records the first time the client opens their link.
     */
    public function markOpened(ClientQuestionnaire $questionnaire): void
    {
        if ($questionnaire->first_opened_at === null) {
            $questionnaire->first_opened_at = now();
            $questionnaire->saveQuietly();
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $answers  Normalised answers for the enabled sections.
     */
    public function saveAnswers(ClientQuestionnaire $questionnaire, array $answers): void
    {
        $questionnaire->answers = $answers;
        $questionnaire->last_saved_at = now();

        if ($questionnaire->status === QuestionnaireStatus::Pending) {
            $questionnaire->status = QuestionnaireStatus::InProgress;
        }

        $questionnaire->save();
    }

    /**
     * @param  array<string, array<string, mixed>>  $answers
     */
    public function submit(ClientQuestionnaire $questionnaire, array $answers): void
    {
        $questionnaire->answers = $answers;
        $questionnaire->status = QuestionnaireStatus::Submitted;
        $questionnaire->last_saved_at = now();
        $questionnaire->submitted_at = now();
        $questionnaire->reviewed_at = null;
        $questionnaire->save();

        $this->audit->record('onboarding.submitted', $questionnaire, 'Client submitted the onboarding questionnaire for '.$questionnaire->project_name);
        $this->notifier->questionnaireSubmitted($questionnaire);
    }

    public function storeFile(ClientQuestionnaire $questionnaire, UploadedFile $upload): ClientQuestionnaireFile
    {
        if ($questionnaire->files()->count() >= (int) config('dxcovr.onboarding.max_files', 15)) {
            throw new RuntimeException('The upload limit for this questionnaire has been reached.');
        }

        $disk = (string) config('dxcovr.onboarding.disk', 'local');
        $extension = Str::lower($upload->guessExtension() ?: $upload->getClientOriginalExtension());
        $path = Storage::disk($disk)->putFileAs('onboarding/'.$questionnaire->id, $upload, Str::random(32).'.'.$extension, ['visibility' => 'private']);

        if ($path === false) {
            throw new RuntimeException('The file could not be stored.');
        }

        return DB::transaction(fn () => $questionnaire->files()->create([
            'disk' => $disk,
            'path' => $path,
            'original_name' => Str::limit(preg_replace('/[^\pL\pN\s._()-]+/u', '', $upload->getClientOriginalName()) ?: 'file.'.$extension, 200, ''),
            'mime_type' => (string) $upload->getMimeType(),
            'size' => (int) $upload->getSize(),
        ]));
    }

    /**
     * Streams an uploaded file. Images display inline; PDFs download. The
     * response is sandboxed so a crafted file can never run script.
     */
    public function fileResponse(ClientQuestionnaireFile $file, bool $download = false): StreamedResponse
    {
        $disk = Storage::disk($file->disk);
        abort_unless($disk->exists($file->path), 404);

        $headers = [
            'Content-Type' => $file->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'no-store, private',
            'Content-Security-Policy' => "default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; sandbox",
        ];

        return $download || ! $file->isImage()
            ? $disk->download($file->path, $file->original_name, $headers)
            : $disk->response($file->path, $file->original_name, $headers);
    }

    public function removeFile(ClientQuestionnaireFile $file): void
    {
        $file->delete();
    }

    /**
     * Lets the client edit and submit again after a submission.
     */
    public function reopen(ClientQuestionnaire $questionnaire): void
    {
        $questionnaire->status = QuestionnaireStatus::InProgress;
        $questionnaire->submitted_at = null;
        $questionnaire->save();

        $this->audit->record('onboarding.reopened', $questionnaire, 'Reopened the questionnaire for '.$questionnaire->project_name);
    }

    /**
     * Switches an unsubmitted questionnaire to the builder's latest questions.
     * Answers to questions that still exist are kept, sections the client was
     * given stay included and sections added since are included too.
     */
    public function refreshQuestions(ClientQuestionnaire $questionnaire): void
    {
        $previous = $questionnaire->definition();
        $latest = QuestionnaireTemplate::definition();
        $chosen = $questionnaire->sections;

        $questionnaire->sections = array_values(array_filter(
            array_keys(QuestionnaireForm::optionalSections($latest)),
            fn (string $key) => $chosen === null
                || in_array($key, $chosen, true)
                || ! array_key_exists($key, $previous)
                || ! empty($previous[$key]['always']),
        ));

        if ($questionnaire->answers !== null) {
            $questionnaire->answers = collect($questionnaire->answers)
                ->map(fn ($values, string $section) => array_intersect_key((array) $values, $latest[$section]['fields'] ?? []))
                ->filter()
                ->all();
        }

        $questionnaire->form = $latest;
        $questionnaire->save();

        $this->audit->record('onboarding.questions_refreshed', $questionnaire, 'Updated '.$questionnaire->project_name.' to the latest questionnaire questions');
    }

    /**
     * Replaces the share link. The previous link stops working immediately.
     */
    public function regenerateLink(ClientQuestionnaire $questionnaire): void
    {
        $questionnaire->issueToken();
        $questionnaire->save();

        $this->audit->record('onboarding.link_regenerated', $questionnaire, 'Generated a new link for '.$questionnaire->project_name);
    }

    public function delete(ClientQuestionnaire $questionnaire): void
    {
        $label = $questionnaire->project_name.' ('.$questionnaire->client_name.')';
        $directory = 'onboarding/'.$questionnaire->id;
        $disk = (string) config('dxcovr.onboarding.disk', 'local');

        $questionnaire->delete();
        Storage::disk($disk)->deleteDirectory($directory);

        $this->audit->record('onboarding.deleted', null, 'Deleted the questionnaire for '.$label);
    }
}
