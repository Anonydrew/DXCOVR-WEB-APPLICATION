<?php

namespace App\Models;

use App\Enums\MediaKind;
use Database\Factories\MediaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;

/**
 * A public website asset. Private recruitment documents never use this model.
 *
 * @property array<int, string>|null $variants Width => path of generated WebP renditions.
 */
#[Fillable(['title', 'alt', 'caption', 'folder'])]
class Media extends Model
{
    /** @use HasFactory<MediaFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'kind' => MediaKind::class,
            'variants' => 'array',
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(MediaAttachment::class);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($term): void {
            $query->where('original_name', 'like', "%{$term}%")
                ->orWhere('title', 'like', "%{$term}%")
                ->orWhere('alt', 'like', "%{$term}%");
        });
    }

    public function scopeImages(Builder $query): Builder
    {
        return $query->where('kind', MediaKind::Image->value);
    }

    public function isImage(): bool
    {
        return $this->kind === MediaKind::Image;
    }

    public function url(): string
    {
        return $this->fileUrl($this->path);
    }

    /**
     * Files stored on this site are linked through the host the visitor is
     * actually using, so a different APP_URL host (localhost vs 127.0.0.1,
     * www vs apex) cannot leave images blocked by the Content Security
     * Policy. External storage URLs (e.g. S3) are returned unchanged.
     */
    protected function fileUrl(string $path): string
    {
        $url = Storage::disk($this->disk)->url($path);
        $appUrl = rtrim((string) config('app.url'), '/');

        if ($appUrl !== '' && str_starts_with($url, $appUrl.'/')) {
            return url(substr($url, strlen($appUrl)));
        }

        return $url;
    }

    /**
     * URL of the smallest generated rendition at least as wide as requested.
     */
    public function variantUrl(int $width): string
    {
        $variants = collect($this->variants ?? [])->sortKeys();
        $match = $variants->first(fn (string $path, int $variantWidth) => $variantWidth >= $width) ?? $variants->last();

        return $match ? $this->fileUrl($match) : $this->url();
    }

    public function srcset(): ?string
    {
        if (empty($this->variants)) {
            return null;
        }

        $candidates = collect($this->variants)
            ->sortKeys()
            ->map(fn (string $path, int $width) => $this->fileUrl($path).' '.$width.'w');

        // Offer the full-size original too, so high-density screens and
        // full-width heroes are never limited to the largest rendition.
        if ($this->width && $this->width > (int) collect($this->variants)->keys()->max()) {
            $candidates->push($this->url().' '.$this->width.'w');
        }

        return $candidates->implode(', ');
    }

    public function humanSize(): string
    {
        return Number::fileSize($this->size, precision: 1);
    }

    public function displayName(): string
    {
        return $this->title ?: $this->original_name;
    }

    /**
     * All storage paths owned by this record (original and renditions).
     *
     * @return list<string>
     */
    public function storagePaths(): array
    {
        return array_values(array_unique(array_merge([$this->path], array_values($this->variants ?? []))));
    }
}
