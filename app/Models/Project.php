<?php

namespace App\Models;

use App\Enums\DeliveryStatus;
use App\Enums\ProjectType;
use App\Models\Concerns\HasMediaAttachments;
use App\Models\Concerns\HasPublication;
use App\Models\Concerns\HasSeo;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\TracksEditors;
use App\Support\VideoEmbed;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * A portfolio case study.
 *
 * @property list<string>|null $video_urls
 * @property list<array{title: string, body: string|null}>|null $features
 */
#[Fillable([
    'title', 'slug', 'summary', 'tagline', 'project_category_id', 'project_type', 'delivery_status', 'client_name',
    'is_confidential', 'anonymised_client_label', 'duration', 'year', 'cover_media_id', 'video_urls', 'challenge',
    'objectives', 'approach', 'solution', 'features', 'role', 'results', 'external_url', 'is_featured', 'sort_order',
])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory, HasMediaAttachments, HasPublication, HasSeo, HasSlug, TracksEditors;

    protected function casts(): array
    {
        return [
            'project_type' => ProjectType::class,
            'delivery_status' => DeliveryStatus::class,
            'is_confidential' => 'boolean',
            'is_featured' => 'boolean',
            'video_urls' => 'array',
            'features' => 'array',
            'year' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProjectCategory::class, 'project_category_id');
    }

    public function cover(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class);
    }

    public function technologies(): BelongsToMany
    {
        return $this->belongsToMany(Technology::class)->orderBy('name');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    public function teamMembers(): BelongsToMany
    {
        return $this->belongsToMany(TeamMember::class);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByDesc('is_featured')->orderBy('sort_order')->orderByDesc('published_at');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(fn (Builder $query) => $query
            ->where('title', 'like', "%{$term}%")
            ->orWhere('summary', 'like', "%{$term}%")
            ->orWhere('solution', 'like', "%{$term}%"));
    }

    /**
     * The client name that may be shown publicly.
     */
    /**
     * One short line for compact tiles: the tagline, else the summary's first sentence.
     */
    public function shortLine(): string
    {
        if (filled($this->tagline)) {
            return $this->tagline;
        }

        $sentence = Str::of((string) $this->summary)->squish()->match('/^.+?[.!?](?=\s|$)/')->toString() ?: (string) $this->summary;

        return Str::limit(rtrim($sentence, '.'), 90);
    }

    public function publicClientLabel(): ?string
    {
        if ($this->is_confidential) {
            return $this->anonymised_client_label ?: 'Confidential client';
        }

        return $this->client_name;
    }

    public function publicUrl(): string
    {
        return route('work.show', $this);
    }

    /**
     * Embeddable player URLs for the supported video hosts.
     *
     * @return list<array{src: string, provider: string}>
     */
    public function videoEmbeds(): array
    {
        return collect($this->video_urls ?? [])
            ->map(fn (string $url) => VideoEmbed::fromUrl($url))
            ->filter()
            ->values()
            ->all();
    }

    public function missingForPublication(): array
    {
        $missing = [];

        if (blank($this->summary)) {
            $missing[] = 'A summary is required.';
        }

        if (blank(strip_tags((string) $this->challenge)) && blank(strip_tags((string) $this->solution))) {
            $missing[] = 'Describe at least the challenge or the solution.';
        }

        if ($this->cover_media_id && blank($this->cover?->alt)) {
            $missing[] = 'The cover image needs alternative text (edit it in the media library).';
        }

        if (! $this->is_confidential && $this->project_type === ProjectType::Client && blank($this->client_name)) {
            $missing[] = 'Add the client name, or mark the project as confidential.';
        }

        return $missing;
    }
}
