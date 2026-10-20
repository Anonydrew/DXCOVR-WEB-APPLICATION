<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Database\Factories\TeamMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property list<array{label: string, url: string}>|null $links
 * @property list<string>|null $skills
 * @property list<string>|null $expertise
 * @property list<array{role: string, organisation: string|null, period: string|null, summary: string|null}>|null $experience
 */
#[Fillable([
    'user_id', 'name', 'slug', 'role_title', 'short_bio', 'bio', 'photo_media_id', 'links', 'skills', 'expertise',
    'experience', 'is_published', 'sort_order',
])]
class TeamMember extends Model
{
    /** @use HasFactory<TeamMemberFactory> */
    use HasFactory, HasSlug;

    protected function casts(): array
    {
        return [
            'links' => 'array',
            'skills' => 'array',
            'expertise' => 'array',
            'experience' => 'array',
            'is_founder' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    public function slugSourceAttribute(): string
    {
        return 'name';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function photo(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'photo_media_id');
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public static function founder(): ?self
    {
        return static::query()->where('is_founder', true)->first();
    }

    public function initials(): string
    {
        return collect(explode(' ', $this->name))->filter()->take(2)
            ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
    }
}
