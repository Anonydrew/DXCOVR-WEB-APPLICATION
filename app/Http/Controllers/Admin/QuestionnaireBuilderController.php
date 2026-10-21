<?php

namespace App\Http\Controllers\Admin;

use App\Models\ClientQuestionnaire;
use App\Models\QuestionnaireSection;
use App\Support\Onboarding\QuestionnaireTemplate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The questionnaire builder: the sections and questions that new client
 * questionnaires are created with.
 */
class QuestionnaireBuilderController extends AdminController
{
    public function index(): View
    {
        return view('admin.onboarding.questions.index', [
            'sections' => QuestionnaireSection::query()->with('questions')->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    /**
     * Shows the client form with the current questions. Nothing is saved.
     */
    public function preview(Request $request): Response|RedirectResponse
    {
        $questionnaire = new ClientQuestionnaire(['client_name' => '', 'project_name' => 'your project']);
        $questionnaire->form = QuestionnaireTemplate::definition();
        $questionnaire->setRelation('files', collect());

        $sections = $questionnaire->formSections();

        if ($sections === []) {
            return redirect()->route('admin.onboarding.questions.index')->with('error', 'Add a section with at least one question to preview the questionnaire.');
        }

        $step = array_key_exists((string) $request->query('step'), $sections) ? (string) $request->query('step') : array_key_first($sections);

        return response()->view('site.onboarding.show', [
            'questionnaire' => $questionnaire,
            'token' => 'preview',
            'sections' => $sections,
            'step' => $step,
            'preview' => true,
        ]);
    }
}
