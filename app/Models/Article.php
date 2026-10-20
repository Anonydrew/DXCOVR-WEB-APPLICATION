<?php

namespace App\Models;

use App\Models\Concerns\HasPublication;
use App\Models\Concerns\HasSeo;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\TracksEditors;
use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['title', 'slug', 'excerpt', 'body', 'featured_media_id', 'author_id', 'byline', 'show_author', 'show_toc', 'is_featured'])]
class Article extends Model
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory, HasPublication, HasSeo, HasSlug, TracksEditors;

    protected function casts(): array
    {
        return [
            'show_author' => 'boolean',
            'show_toc' => 'boolean',
            'is_featured' => 'boolean',
            'submitted_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function featuredImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_media_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(ArticleCategory::class)->orderBy('sort_order');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->orderBy('name');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($term): void {
            $query->where('title', 'like', "%{$term}%")
                ->orWhere('excerpt', 'like', "%{$term}%")
                ->orWhere('body', 'like', "%{$term}%");
        });
    }

    public function bylineName(): string
    {
        return $this->byline ?: ($this->author?->name ?? 'DXCOVR');
    }

    public function publicUrl(): string
    {
        return route('insights.show', $this);
    }

    public function missingForPublication(): array
    {
        $missing = [];

        if (blank($this->excerpt)) {
            $missing[] = 'An excerpt is required.';
        }

        if (blank(strip_tags((string) $this->body, '<img><iframe>'))) {
            $missing[] = 'The article body is empty.';
        }

        if ($this->categories()->doesntExist()) {
            $missing[] = 'Select at least one category.';
        }

        if ($this->featured_media_id && blank($this->featuredImage?->alt)) {
            $missing[] = 'The featured image needs alternative text (edit it in the media library).';
        }

        return $missing;
    }
}
