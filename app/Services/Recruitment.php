<?php

namespace App\Services;

use App\Enums\ApplicationStage;
use App\Models\ApplicationNote;
use App\Models\JobApplication;
use App\Models\JobListing;
use App\Models\User;
use App\Notifications\ApplicationStageUpdated;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Application intake and pipeline management.
 *
 * CVs are written to a private disk under a random name inside a
 * per-application directory; they are only ever streamed back through the
 * authorised admin download route.
 */
class Recruitment
{
    public function __construct(
        protected AuditLogger $audit,
        protected InboxNotifier $notifier,
    ) {}

    /**
     * @param  array{name: string, email: string, phone?: string|null, cover_letter: string, portfolio_url?: string|null}  $data
     */
    public function submit(JobListing $job, array $data, UploadedFile $cv, ?string $ip, ?string $userAgent): JobApplication
    {
        $disk = (string) config('dxcovr.careers.cv_disk', 'local');
        $extension = strtolower((string) $cv->guessExtension()) ?: strtolower($cv->getClientOriginalExtension());
        $directory = 'applications/'.now()->format('Y/m').'/'.Str::lower((string) Str::ulid());
        $path = Storage::disk($disk)->putFileAs($directory, $cv, 'cv.'.$extension, ['visibility' => 'private']);

        if ($path === false) {
            throw new \RuntimeException('The CV could not be stored.');
        }

        try {
            $application = DB::transaction(function () use ($job, $data, $cv, $disk, $path, $ip, $userAgent): JobApplication {
                $application = $job->applications()->create([
                    'name' => $data['name'],
                    'email' => Str::lower($data['email']),
                    'phone' => $data['phone'] ?? null,
                    'cover_letter' => $data['cover_letter'],
                    'portfolio_url' => $data['portfolio_url'] ?? null,
                    'cv_disk' => $disk,
                    'cv_path' => $path,
                    'cv_original_name' => Str::limit(preg_replace('/[^\pL\pN\s._-]+/u', '', $cv->getClientOriginalName()) ?: 'cv', 200, ''),
                    'cv_mime' => (string) $cv->getMimeType(),
                    'cv_size' => (int) $cv->getSize(),
                    'consent_given_at' => now(),
                    'ip_address' => $ip,
                    'user_agent' => Str::limit((string) $userAgent, 490, ''),
                ]);

                $application->stageChanges()->create([
                    'to_stage' => ApplicationStage::Received->value,
                    'note' => 'Application submitted through the website.',
                    'created_at' => now(),
                ]);

                return $application;
            });
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($path);

            throw $exception;
        }

        $application->setRelation('jobListing', $job);
        $this->notifier->applicationReceived($application);

        return $application;
    }

    public function changeStage(JobApplication $application, ApplicationStage $stage, ?User $by, ?string $note = null, bool $notifyApplicant = false, ?string $messageToApplicant = null): void
    {
        $from = $application->stage;

        if ($from === $stage && blank($note)) {
            return;
        }

        DB::transaction(function () use ($application, $stage, $by, $note, $notifyApplicant, $from): void {
            $application->stage = $stage;
            $application->stage_changed_at = now();
            $application->decided_at = $stage->isFinal() ? ($application->decided_at ?? now()) : null;
            $application->save();

            $application->stageChanges()->create([
                'user_id' => $by?->getKey(),
                'from_stage' => $from?->value,
                'to_stage' => $stage->value,
                'note' => $note,
                'applicant_notified' => $notifyApplicant,
                'created_at' => now(),
            ]);
        });

        $this->audit->record('applications.stage_changed', $application, 'Moved application '.$application->reference.' from '.($from?->label() ?? 'none').' to '.$stage->label(), [
            'from' => $from?->value,
            'to' => $stage->value,
            'applicant_notified' => $notifyApplicant,
        ]);

        if ($notifyApplicant) {
            $application->loadMissing('jobListing');
            $this->notifier->sendTo($application->email, new ApplicationStageUpdated($application, $messageToApplicant), 'application stage', $application->reference);
        }
    }

    public function addNote(JobApplication $application, User $by, string $body): ApplicationNote
    {
        $note = $application->notes()->create(['user_id' => $by->getKey(), 'body' => $body]);

        $this->audit->record('applications.note_added', $application, 'Added a note to application '.$application->reference);

        return $note;
    }

    /**
     * Permanently delete an application, its notes, history and CV file.
     */
    public function delete(JobApplication $application, string $reason): void
    {
        $disk = $application->cv_disk;
        $path = $application->cv_path;
        $reference = $application->reference;

        DB::transaction(fn () => $application->delete());

        try {
            Storage::disk($disk)->delete($path);
            Storage::disk($disk)->deleteDirectory(dirname($path));
        } catch (Throwable $exception) {
            Log::error('Unable to delete CV file for a removed application.', ['reference' => $reference, 'error' => $exception->getMessage()]);
        }

        $this->audit->record('applications.deleted', null, 'Deleted application '.$reference, ['reason' => $reason]);
    }
}
