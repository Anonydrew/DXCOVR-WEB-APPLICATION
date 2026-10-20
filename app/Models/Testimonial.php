<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A client testimonial. Only shown publicly when consent to publish has been confirmed.
 */
#[Fillable(['quote', 'author_name', 'author_title', 'organisation', 'project_id', 'photo_media_id', 'consent_confirmed', 'is_published', 'sort_order'])]
class Testimonial extends Model
{
    protected function casts(): array
    {
        return [
            'consent_confirmed' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function photo(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'photo_media_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->where('consent_confirmed', true);
    }
}
