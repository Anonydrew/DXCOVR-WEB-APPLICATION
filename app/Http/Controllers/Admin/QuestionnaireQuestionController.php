<?php

namespace App\Http\Controllers\Admin;

use App\Models\QuestionnaireQuestion;
use App\Models\QuestionnaireSection;
use App\Support\Onboarding\QuestionnaireForm;
use App\Support\Onboarding\QuestionnaireTemplate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Questions of the questionnaire template. A question's storage key and its
 * options' values are fixed once created, so rewording never orphans answers.
 */
class QuestionnaireQuestionController extends AdminController
{
    public function create(Request $request): View|RedirectResponse
    {
        $section = QuestionnaireSection::query()->find($request->integer('section'))
            ?? QuestionnaireSection::query()->orderBy('sort_order')->first();

        if (! $section) {
            return redirect()->route('admin.onboarding.sections.create')->with('error', 'Add a section first, then its questions.');
        }

        $question = new QuestionnaireQuestion(['type' => 'text', 'is_required' => false]);
        $question->questionnaire_section_id = $section->id;

        return view('admin.onboarding.questions.question-form', $this->formData($question));
    }

    public function store(Request $request): RedirectResponse
    {
        $question = new QuestionnaireQuestion;
        $this->apply($request, $question);

        $this->audit()->record('onboarding.question_created', $question, 'Added the questionnaire question “'.$question->label.'”');

        return redirect()->to(route('admin.onboarding.questions.index').'#section-'.$question->questionnaire_section_id)->with('status', 'Question added.');
    }

    public function edit(QuestionnaireQuestion $question): View
    {
        return view('admin.onboarding.questions.question-form', $this->formData($question));
    }

    public function update(Request $request, QuestionnaireQuestion $question): RedirectResponse
    {
        $this->apply($request, $question);

        $this->audit()->record('onboarding.question_updated', $question, 'Updated the questionnaire question “'.$question->label.'”', $this->audit()->changes($question));

        return redirect()->to(route('admin.onboarding.questions.index').'#section-'.$question->questionnaire_section_id)->with('status', 'Question saved.');
    }

    public function destroy(QuestionnaireQuestion $question): RedirectResponse
    {
        if ($question->isSystem()) {
            return back()->with('error', 'This question is needed for every questionnaire and cannot be deleted. You can reword it instead.');
        }

        $label = $question->label;
        $sectionId = $question->questionnaire_section_id;
        $question->delete();

        $this->audit()->record('onboarding.question_deleted', null, 'Deleted the questionnaire question “'.$label.'”');

        return redirect()->to(route('admin.onboarding.questions.index').'#section-'.$sectionId)->with('status', 'Question deleted. Questionnaires already created are not affected.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function formData(QuestionnaireQuestion $question): array
    {
        return [
            'question' => $question,
            'sections' => QuestionnaireSection::query()->orderBy('sort_order')->orderBy('id')->pluck('title', 'id')->all(),
            'locked' => $question->exists && $question->isSystem(),
        ];
    }

    /**
     * Validates the request and saves it onto the question.
     */
    protected function apply(Request $request, QuestionnaireQuestion $question): void
    {
        $locked = $question->exists && $question->isSystem();

        $data = $request->validate([
            'questionnaire_section_id' => [$locked ? 'nullable' : 'required', 'integer', Rule::exists('questionnaire_sections', 'id')],
            'type' => [$locked ? 'nullable' : 'required', 'string', Rule::in(array_keys(QuestionnaireForm::TYPES))],
            'label' => ['required', 'string', 'max:500'],
            'hint' => ['nullable', 'string', 'max:500'],
            'is_required' => ['boolean'],
            'options' => ['nullable', 'array', 'max:40'],
            'options.*.label' => ['nullable', 'string', 'max:200'],
            'options.*.value' => ['nullable', 'string', 'max:60', 'regex:/^[a-z0-9][a-z0-9_]*$/'],
            'max_length' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'max_choices' => ['nullable', 'integer', 'min:1', 'max:40'],
        ], [], [
            'questionnaire_section_id' => 'section',
            'type' => 'answer type',
            'label' => 'question',
            'options.*.label' => 'option',
            'max_length' => 'character limit',
            'max_choices' => 'limit',
        ]);

        $sectionId = $locked ? $question->questionnaire_section_id : (int) $data['questionnaire_section_id'];
        $type = $locked ? $question->type : $data['type'];
        $options = in_array($type, QuestionnaireForm::CHOICE_TYPES, true) ? $this->options($data['options'] ?? []) : null;
        $maxChoices = in_array($type, [...QuestionnaireForm::MULTI_TYPES, 'references'], true) ? ($data['max_choices'] ?? null) : null;

        $this->guard($question, $type, $options, $maxChoices);

        $question->fill([
            'type' => $type,
            'label' => $data['label'],
            'hint' => $data['hint'] ?? null,
            'is_required' => $type === 'consent' || ($type !== 'uploads' && $request->boolean('is_required')),
            'options' => $options,
            'max_length' => isset(QuestionnaireForm::LENGTH_TYPES[$type]) ? ($data['max_length'] ?? null) : null,
            'max_choices' => $maxChoices,
        ]);

        // Keys never change once answers may exist under them, except to stay
        // unique when a question moves to a section that already uses the key.
        if (! $question->exists || (int) $question->questionnaire_section_id !== $sectionId) {
            $taken = QuestionnaireQuestion::query()->where('questionnaire_section_id', $sectionId)->whereKeyNot($question->id)->pluck('key')->all();

            if (! $question->exists || in_array($question->key, $taken, true)) {
                $question->key = QuestionnaireTemplate::makeKey($question->exists ? $question->key : $data['label'], $taken, 'question');
            }

            $question->sort_order = (int) QuestionnaireQuestion::query()->where('questionnaire_section_id', $sectionId)->max('sort_order') + 1;
            $question->questionnaire_section_id = $sectionId;
        }

        $question->save();
    }

    /**
     * Option rows with a label, each keeping its stored value or given a new one.
     *
     * @param  array<int, array{label?: string|null, value?: string|null}>  $rows
     * @return list<array{value: string, label: string}>
     */
    protected function options(array $rows): array
    {
        $options = [];
        $taken = [];

        foreach ($rows as $row) {
            $label = trim((string) ($row['label'] ?? ''));

            if ($label === '') {
                continue;
            }

            $value = (string) ($row['value'] ?? '');

            if ($value === '' || in_array($value, $taken, true)) {
                $value = QuestionnaireTemplate::makeKey($label, $taken, 'option');
            }

            $taken[] = $value;
            $options[] = ['value' => $value, 'label' => $label];
        }

        return $options;
    }

    /**
     * Rules that depend on the type and on the rest of the questionnaire.
     *
     * @param  list<array{value: string, label: string}>|null  $options
     */
    protected function guard(QuestionnaireQuestion $question, string $type, ?array $options, ?int $maxChoices): void
    {
        if (in_array($type, QuestionnaireForm::CHOICE_TYPES, true) && count($options ?? []) < 2) {
            throw ValidationException::withMessages(['options' => 'Add at least two options for clients to choose from.']);
        }

        $available = match ($type) {
            'checkboxes' => count($options ?? []),
            'styles' => count(QuestionnaireForm::STYLES),
            'palettes' => count(QuestionnaireForm::PALETTES),
            'references' => 20,
            default => null,
        };

        if ($maxChoices !== null && $available !== null && $maxChoices > $available) {
            throw ValidationException::withMessages(['max_choices' => 'The limit can be at most '.$available.'.']);
        }

        if ($type === 'uploads' && QuestionnaireQuestion::query()->where('type', 'uploads')->whereKeyNot($question->id)->exists()) {
            throw ValidationException::withMessages(['type' => 'The questionnaire already has a file upload question. Files are collected in one place, so there can only be one.']);
        }
    }
}
