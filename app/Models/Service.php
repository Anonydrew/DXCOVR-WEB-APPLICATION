<?php

namespace App\Models;

use App\Models\Concerns\HasPublication;
use App\Models\Concerns\HasSeo;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\TracksEditors;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property list<string>|null $capabilities
 * @property list<array{title: string, body: string|null}>|null $use_cases
 * @property list<array{title: string, body: string|null}>|null $process_steps
 */
#[Fillable([
    'title', 'slug', 'icon', 'short_description', 'body', 'hero_media_id', 'hero_video_url', 'loop_video_media_id', 'capabilities',
    'use_cases', 'process_steps', 'cta_heading', 'cta_body', 'cta_label', 'cta_url', 'is_featured', 'sort_order',
])]
class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory, HasPublication, HasSeo, HasSlug, TracksEditors;

    /**
     * Visual motifs available for services (rendered by <x-site.glyph>).
     */
    public const ICONS = [
        'software' => 'Software & systems',
        'ai' => 'Artificial intelligence',
        'hardware' => 'Hardware & embedded',
        'xr' => 'AR & VR',
        'webxr' => 'WebXR',
        'modelling' => '3D modelling',
        'design' => 'Web, UI/UX & design',
        'support' => 'IT & support',
    ];

    protected function casts(): array
    {
        return [
            'capabilities' => 'array',
            'use_cases' => 'array',
            'process_steps' => 'array',
            'is_featured' => 'boolean',
        ];
    }

    public function heroImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'hero_media_id');
    }

    public function loopVideo(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'loop_video_media_id');
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(Faq::class)->orderBy('sort_order');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('title');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(fn (Builder $query) => $query
            ->where('title', 'like', "%{$term}%")
            ->orWhere('short_description', 'like', "%{$term}%")
            ->orWhere('body', 'like', "%{$term}%"));
    }

    public function publicUrl(): string
    {
        return route('services.show', $this);
    }

    public function missingForPublication(): array
    {
        return blank($this->short_description) ? ['A short description is required.'] : [];
    }
}
