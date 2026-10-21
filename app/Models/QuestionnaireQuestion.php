<?php

namespace App\Models;

use App\Support\Onboarding\QuestionnaireForm;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A question in the onboarding questionnaire template. Options are stored as
 * an ordered list of ['value' => …, 'label' => …]; values stay fixed when a
 * label is reworded so earlier answers still match.
 */
#[Fillable(['type', 'label', 'hint', 'is_required', 'options', 'max_length', 'max_choices'])]
class QuestionnaireQuestion extends Model
{
    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'options' => 'array',
            'max_length' => 'integer',
            'max_choices' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(QuestionnaireSection::class, 'questionnaire_section_id');
    }

    /**
     * Questions the application relies on: they can be reworded but not removed, moved or retyped.
     */
    public function isSystem(): bool
    {
        return in_array($this->section?->key.'.'.$this->key, QuestionnaireForm::SYSTEM_QUESTIONS, true);
    }

    public function typeLabel(): string
    {
        return QuestionnaireForm::TYPES[$this->type] ?? $this->type;
    }

    /**
     * The question as a questionnaire field definition.
     *
     * @return array<string, mixed>
     */
    public function toField(): array
    {
        $type = $this->type;
        $field = ['type' => $type, 'label' => $this->label];

        if (filled($this->hint)) {
            $field['hint'] = $this->hint;
        }

        // Confirmation ticks are always needed to submit; uploads never are.
        $field['required'] = $type === 'consent' || ($type !== 'uploads' && $this->is_required);

        if (isset(QuestionnaireForm::LENGTH_TYPES[$type])) {
            $field['max'] = $this->max_length ?: QuestionnaireForm::LENGTH_TYPES[$type];
        }

        if (in_array($type, QuestionnaireForm::MULTI_TYPES, true) && $this->max_choices) {
            $field['max_items'] = $this->max_choices;
        }

        if ($type === 'references') {
            $field['max_items'] = $this->max_choices ?: QuestionnaireForm::DEFAULT_REFERENCES;
        }

        if (in_array($type, QuestionnaireForm::CHOICE_TYPES, true)) {
            $field['options'] = collect($this->options ?? [])->mapWithKeys(fn (array $option) => [$option['value'] => $option['label']])->all();
        }

        return $field;
    }
}
