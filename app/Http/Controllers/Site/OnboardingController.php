<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\ClientQuestionnaire;
use App\Models\ClientQuestionnaireFile;
use App\Rules\ReadableUpload;
use App\Services\ClientOnboarding;
use App\Support\Onboarding\QuestionnaireForm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator as ValidatorInstance;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The client-facing questionnaire, reached only through its private link.
 */
class OnboardingController extends Controller
{
    public function __construct(protected ClientOnboarding $onboarding) {}

    public function show(Request $request, string $token): Response
    {
        $questionnaire = $this->resolve($token);

        if ($questionnaire->isExpired()) {
            return response()->view('site.onboarding.unavailable', ['questionnaire' => $questionnaire], 410);
        }

        // Staff previewing the link should not look like the client opening it.
        if (! $request->user()) {
            $this->onboarding->markOpened($questionnaire);
        }

        if ($questionnaire->isSubmitted()) {
            return response()->view('site.onboarding.submitted', ['questionnaire' => $questionnaire]);
        }

        $sections = $questionnaire->formSections();
        $step = array_key_exists((string) $request->query('step'), $sections) ? (string) $request->query('step') : array_key_first($sections);

        return response()->view('site.onboarding.show', [
            'questionnaire' => $questionnaire->load('files'),
            'token' => $token,
            'sections' => $sections,
            'step' => $step,
        ]);
    }

    public function update(Request $request, string $token): RedirectResponse|JsonResponse
    {
        $questionnaire = $this->resolve($token);

        if (! $questionnaire->acceptsAnswers()) {
            return $request->wantsJson()
                ? response()->json(['message' => 'This questionnaire can no longer be changed.'], 409)
                : redirect()->route('onboarding.show', $token);
        }

        $sections = $questionnaire->formSections();
        $submitting = $request->input('action') === 'submit';
        $currentStep = array_key_exists((string) $request->input('step'), $sections) ? (string) $request->input('step') : null;

        $validator = $this->validator($request, $questionnaire, $sections, $submitting);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Please check the highlighted answers.', 'errors' => $validator->errors()], 422);
            }

            return redirect()
                ->route('onboarding.show', ['token' => $token, 'step' => $this->firstErrorStep($validator, $sections) ?? $currentStep])
                ->withErrors($validator)
                ->withInput();
        }

        $answers = QuestionnaireForm::normalise($validator->validated()['answers'] ?? [], $sections);

        foreach ($request->file('files', []) as $upload) {
            $this->onboarding->storeFile($questionnaire, $upload);
        }

        if ($request->filled('remove_file')) {
            $file = $questionnaire->files()->whereKey((int) $request->input('remove_file'))->first();
            $file && $this->onboarding->removeFile($file);
        }

        if ($submitting) {
            $this->onboarding->submit($questionnaire, $answers);

            return redirect()->route('onboarding.show', $token);
        }

        $this->onboarding->saveAnswers($questionnaire, $answers);

        if ($request->wantsJson()) {
            return response()->json(['saved_at' => $questionnaire->last_saved_at->toIso8601String()]);
        }

        return redirect()
            ->route('onboarding.show', ['token' => $token, 'step' => $request->filled('remove_file') ? 'inspiration' : $currentStep])
            ->with('status', $request->filled('remove_file') ? 'File removed.' : 'Your answers are saved. You can close this page and continue later with the same link.');
    }

    public function upload(Request $request, string $token): JsonResponse
    {
        $questionnaire = $this->resolve($token);
        abort_unless($questionnaire->acceptsAnswers(), 409);

        $request->validate(['file' => $this->fileRules()], [], ['file' => 'file']);

        try {
            $file = $this->onboarding->storeFile($questionnaire, $request->file('file'));
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json($this->filePayload($token, $file), 201);
    }

    public function file(string $token, int $file): StreamedResponse
    {
        return $this->onboarding->fileResponse($this->ownedFile($this->resolve($token), $file));
    }

    public function destroyFile(string $token, int $file): JsonResponse
    {
        $questionnaire = $this->resolve($token);
        abort_unless($questionnaire->acceptsAnswers(), 409);

        $this->onboarding->removeFile($this->ownedFile($questionnaire, $file));

        return response()->json(['removed' => true]);
    }

    protected function resolve(string $token): ClientQuestionnaire
    {
        return ClientQuestionnaire::findByToken($token) ?? abort(404);
    }

    protected function ownedFile(ClientQuestionnaire $questionnaire, int $id): ClientQuestionnaireFile
    {
        return $questionnaire->files()->whereKey($id)->firstOrFail();
    }

    /**
     * @param  array<string, array<string, mixed>>  $sections
     */
    protected function validator(Request $request, ClientQuestionnaire $questionnaire, array $sections, bool $submitting): ValidatorInstance
    {
        $answers = QuestionnaireForm::prepare((array) $request->input('answers', []), $sections);
        $remaining = max(0, (int) config('dxcovr.onboarding.max_files', 15) - $questionnaire->files()->count());

        return Validator::make(
            ['answers' => $answers, 'files' => $request->file('files', [])],
            array_merge(QuestionnaireForm::rules($sections, $submitting), [
                'files' => ['nullable', 'array', 'max:'.$remaining],
                'files.*' => $this->fileRules(),
            ]),
            ['files.max' => $remaining === 0 ? 'You have reached the upload limit. Remove a file to add another.' : 'You can upload :max more files.'],
            QuestionnaireForm::attributes($sections) + ['files.*' => 'file'],
        );
    }

    /**
     * @return list<mixed>
     */
    protected function fileRules(): array
    {
        return [
            'bail', 'required', 'file', new ReadableUpload,
            'mimes:'.implode(',', config('dxcovr.onboarding.extensions')),
            'max:'.config('dxcovr.onboarding.max_kb'),
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $sections
     */
    protected function firstErrorStep(ValidatorInstance $validator, array $sections): ?string
    {
        $key = (string) array_key_first($validator->errors()->messages());

        if (Str::startsWith($key, 'files')) {
            return 'inspiration';
        }

        $section = explode('.', $key)[1] ?? null;

        return array_key_exists((string) $section, $sections) ? $section : null;
    }

    /**
     * @return array{id: int, name: string, size: string, is_image: bool, url: string, remove_url: string}
     */
    protected function filePayload(string $token, ClientQuestionnaireFile $file): array
    {
        return [
            'id' => $file->id,
            'name' => $file->original_name,
            'size' => $file->humanSize(),
            'is_image' => $file->isImage(),
            'url' => route('onboarding.files.show', [$token, $file->id]),
            'remove_url' => route('onboarding.files.destroy', [$token, $file->id]),
        ];
    }
}
