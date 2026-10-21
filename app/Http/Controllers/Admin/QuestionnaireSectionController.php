<?php

namespace App\Http\Controllers\Admin;

use App\Models\QuestionnaireSection;
use App\Support\Onboarding\QuestionnaireTemplate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Sections (steps) of the questionnaire template. Questionnaires already
 * created keep their own copy, so changes here only affect new ones.
 */
class QuestionnaireSectionController extends AdminController
{
    public function create(): View
    {
        return view('admin.onboarding.questions.section-form', ['section' => new QuestionnaireSection(['always_included' => false])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $section = new QuestionnaireSection($this->validated($request));
        $section->key = QuestionnaireTemplate::makeKey($section->title, QuestionnaireSection::query()->pluck('key')->all(), 'section');
        $section->sort_order = (int) QuestionnaireSection::query()->max('sort_order') + 1;
        $section->save();

        $this->audit()->record('onboarding.section_created', $section, 'Added the questionnaire section “'.$section->title.'”');

        return redirect()->to(route('admin.onboarding.questions.index').'#section-'.$section->id)
            ->with('status', 'Section added. Now add its questions.');
    }

    public function edit(QuestionnaireSection $section): View
    {
        return view('admin.onboarding.questions.section-form', ['section' => $section->loadCount('questions')]);
    }

    public function update(Request $request, QuestionnaireSection $section): RedirectResponse
    {
        $section->fill($this->validated($request));

        if ($section->isSystem()) {
            $section->always_included = true;
        }

        $section->save();

        $this->audit()->record('onboarding.section_updated', $section, 'Updated the questionnaire section “'.$section->title.'”', $this->audit()->changes($section));

        return redirect()->to(route('admin.onboarding.questions.index').'#section-'.$section->id)->with('status', 'Section saved.');
    }

    public function destroy(QuestionnaireSection $section): RedirectResponse
    {
        if ($section->isSystem()) {
            return back()->with('error', 'This section is needed for every questionnaire and cannot be deleted. You can reword it instead.');
        }

        $title = $section->title;

        DB::transaction(function () use ($section): void {
            $section->questions()->delete();
            $section->delete();
        });

        $this->audit()->record('onboarding.section_deleted', null, 'Deleted the questionnaire section “'.$title.'” and its questions');

        return redirect()->route('admin.onboarding.questions.index')->with('status', 'Section deleted. Questionnaires already created are not affected.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'intro' => ['nullable', 'string', 'max:1000'],
            'always_included' => ['boolean'],
        ]);

        $data['always_included'] = $request->boolean('always_included');

        return $data;
    }
}
