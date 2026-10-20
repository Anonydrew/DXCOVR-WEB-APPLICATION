<?php

namespace App\Http\Controllers\Admin;

use App\Enums\QuestionnaireStatus;
use App\Http\Requests\Admin\ClientQuestionnaireRequest;
use App\Models\ClientQuestionnaire;
use App\Models\Enquiry;
use App\Services\ClientOnboarding;
use App\Support\Onboarding\QuestionnaireForm;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientQuestionnaireController extends AdminController
{
    public function __construct(protected ClientOnboarding $onboarding) {}

    public function index(Request $request): View
    {
        $status = in_array($request->query('status'), QuestionnaireStatus::values(), true) ? $request->query('status') : null;

        return view('admin.onboarding.index', [
            'questionnaires' => ClientQuestionnaire::query()
                ->withCount('files')
                ->when($status, fn (Builder $query) => $query->where('status', $status))
                ->search($request->string('q')->limit(100, '')->toString() ?: null)
                ->latest()
                ->paginate(25)
                ->withQueryString(),
            'statusCounts' => ClientQuestionnaire::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function create(Request $request): View
    {
        $questionnaire = new ClientQuestionnaire([
            'sections' => array_keys(QuestionnaireForm::optionalSections()),
            'expires_at' => now()->addDays((int) config('dxcovr.onboarding.link_days', 30)),
        ]);

        if ($enquiry = Enquiry::query()->find($request->integer('enquiry'))) {
            $questionnaire->fill([
                'client_name' => $enquiry->name,
                'client_email' => $enquiry->email,
                'client_company' => $enquiry->organisation,
                'enquiry_id' => $enquiry->id,
            ]);
        }

        return view('admin.onboarding.form', ['questionnaire' => $questionnaire]);
    }

    public function store(ClientQuestionnaireRequest $request): RedirectResponse
    {
        $questionnaire = ClientQuestionnaire::query()->create($request->questionnaireData());

        $this->audit()->record('onboarding.created', $questionnaire, 'Created an onboarding questionnaire for '.$questionnaire->project_name);

        return redirect()->route('admin.onboarding.show', $questionnaire)
            ->with('status', 'Questionnaire created. Copy the link below and share it with your client.');
    }

    public function show(Request $request, ClientQuestionnaire $questionnaire): View
    {
        if ($questionnaire->isSubmitted() && $questionnaire->reviewed_at === null && $request->user()->can('onboarding.manage')) {
            $questionnaire->reviewed_at = now();
            $questionnaire->saveQuietly();
        }

        $questionnaire->load(['files', 'enquiry', 'creator']);

        return view('admin.onboarding.show', [
            'questionnaire' => $questionnaire,
            'sections' => $questionnaire->formSections(),
        ]);
    }

    public function edit(ClientQuestionnaire $questionnaire): View
    {
        return view('admin.onboarding.form', ['questionnaire' => $questionnaire]);
    }

    public function update(ClientQuestionnaireRequest $request, ClientQuestionnaire $questionnaire): RedirectResponse
    {
        $questionnaire->fill($request->questionnaireData())->save();

        $this->audit()->record('onboarding.updated', $questionnaire, 'Updated the questionnaire for '.$questionnaire->project_name, $this->audit()->changes($questionnaire));

        return redirect()->route('admin.onboarding.show', $questionnaire)->with('status', 'Questionnaire updated.');
    }

    public function notes(Request $request, ClientQuestionnaire $questionnaire): RedirectResponse
    {
        $questionnaire->internal_notes = $request->validate(['internal_notes' => ['nullable', 'string', 'max:10000']])['internal_notes'] ?? null;
        $questionnaire->save();

        return back()->with('status', 'Notes saved.');
    }

    public function reopen(ClientQuestionnaire $questionnaire): RedirectResponse
    {
        $this->onboarding->reopen($questionnaire);

        return back()->with('status', 'Reopened. The client can edit and submit their answers again using the same link.');
    }

    public function regenerate(ClientQuestionnaire $questionnaire): RedirectResponse
    {
        $this->onboarding->regenerateLink($questionnaire);

        return back()->with('status', 'A new link has been generated. The previous link no longer works.');
    }

    public function file(Request $request, ClientQuestionnaire $questionnaire, int $file): StreamedResponse
    {
        $upload = $questionnaire->files()->whereKey($file)->firstOrFail();

        if ($request->boolean('download')) {
            $this->audit()->record('onboarding.file_downloaded', $questionnaire, 'Downloaded '.$upload->original_name.' from '.$questionnaire->project_name);
        }

        return $this->onboarding->fileResponse($upload, $request->boolean('download'));
    }

    public function destroy(ClientQuestionnaire $questionnaire): RedirectResponse
    {
        $this->onboarding->delete($questionnaire);

        return redirect()->route('admin.onboarding.index')->with('status', 'Questionnaire and uploaded files deleted.');
    }
}
