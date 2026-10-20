<?php

namespace App\Models;

use App\Enums\ProductStatus;
use App\Models\Concerns\HasMediaAttachments;
use App\Models\Concerns\HasPublication;
use App\Models\Concerns\HasSeo;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\TracksEditors;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property list<array{title: string, body: string|null}>|null $features
 * @property list<array{title: string, body: string|null}>|null $modules
 * @property list<string>|null $integrations
 */
#[Fillable([
    'name', 'slug', 'tagline', 'short_description', 'body', 'logo_media_id', 'hero_media_id', 'product_status',
    'audience', 'features', 'modules', 'integrations', 'documentation_url', 'demo_requests_enabled',
    'enquiries_enabled', 'pricing_visible', 'pricing', 'is_featured', 'sort_order',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, HasMediaAttachments, HasPublication, HasSeo, HasSlug, TracksEditors;

    protected function casts(): array
    {
        return [
            'product_status' => ProductStatus::class,
            'features' => 'array',
            'modules' => 'array',
            'integrations' => 'array',
            'demo_requests_enabled' => 'boolean',
            'enquiries_enabled' => 'boolean',
            'pricing_visible' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function slugSourceAttribute(): string
    {
        return 'name';
    }

    public function logo(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'logo_media_id');
    }

    public function heroImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'hero_media_id');
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class);
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class);
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(Faq::class)->orderBy('sort_order');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByDesc('is_featured')->orderBy('sort_order')->orderBy('name');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(fn (Builder $query) => $query
            ->where('name', 'like', "%{$term}%")
            ->orWhere('tagline', 'like', "%{$term}%")
            ->orWhere('short_description', 'like', "%{$term}%"));
    }

    public function acceptsEnquiries(): bool
    {
        return ($this->enquiries_enabled || $this->demo_requests_enabled)
            && $this->product_status !== ProductStatus::Discontinued;
    }

    public function publicUrl(): string
    {
        return route('products.show', $this);
    }

    public function missingForPublication(): array
    {
        $missing = [];

        if (blank($this->short_description)) {
            $missing[] = 'A short description is required.';
        }

        if ($this->pricing_visible && blank(strip_tags((string) $this->pricing))) {
            $missing[] = 'Pricing is set to visible but no pricing information has been entered.';
        }

        return $missing;
    }
}
