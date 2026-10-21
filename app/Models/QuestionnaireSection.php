<?php

namespace App\Models;

use App\Support\Onboarding\QuestionnaireForm;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A step of the onboarding questionnaire template, edited in the
 * questionnaire builder. The key is fixed once created because answers are
 * stored under it.
 */
#[Fillable(['title', 'intro', 'always_included'])]
class QuestionnaireSection extends Model
{
    protected function casts(): array
    {
        return [
            'always_included' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuestionnaireQuestion::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Sections the application relies on: always included and never deleted.
     */
    public function isSystem(): bool
    {
        return in_array($this->key, QuestionnaireForm::SYSTEM_SECTIONS, true);
    }
}
