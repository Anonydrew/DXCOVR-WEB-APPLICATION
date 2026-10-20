<?php

namespace App\Models\Concerns;

use App\Enums\PublicationStatus;
use App\Support\ContentCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Adds the draft → review → scheduled → published → archived workflow.
 *
 * The "status" and "published_at" attributes are intentionally not mass
 * assignable; they are changed only through App\Services\PublicationService.
 *
 * @property PublicationStatus $status
 * @property Carbon|null $published_at
 */
trait HasPublication
{
    public static function bootHasPublication(): void
    {
        static::saved(fn () => ContentCache::flush());
        static::deleted(fn () => ContentCache::flush());
    }

    public function initializeHasPublication(): void
    {
        $this->mergeCasts([
            'status' => PublicationStatus::class,
            'published_at' => 'datetime',
        ]);

        if (! isset($this->attributes['status'])) {
            $this->attributes['status'] = PublicationStatus::Draft->value;
        }
    }

    /**
     * Content that is published and whose publication time has passed.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where($query->qualifyColumn('status'), PublicationStatus::Published->value)
            ->whereNotNull($query->qualifyColumn('published_at'))
            ->where($query->qualifyColumn('published_at'), '<=', now());
    }

    /**
     * Scheduled content whose publication time has arrived.
     */
    public function scopeDueForPublication(Builder $query): Builder
    {
        return $query
            ->where($query->qualifyColumn('status'), PublicationStatus::Scheduled->value)
            ->whereNotNull($query->qualifyColumn('published_at'))
            ->where($query->qualifyColumn('published_at'), '<=', now());
    }

    public function scopeWithStatus(Builder $query, ?string $status): Builder
    {
        if ($status === null || $status === '' || ! in_array($status, PublicationStatus::values(), true)) {
            return $query;
        }

        return $query->where($query->qualifyColumn('status'), $status);
    }

    public function isPubliclyVisible(): bool
    {
        return $this->status === PublicationStatus::Published
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }

    /**
     * Fields that must be complete before the item can be published or scheduled.
     *
     * @return list<string> Human readable descriptions of missing content.
     */
    public function missingForPublication(): array
    {
        return [];
    }

    /**
     * Human readable name of the content type, used in notices and audit logs.
     */
    public function contentTypeLabel(): string
    {
        return str(class_basename($this))->headline()->lower()->toString();
    }

    /**
     * The value used to identify the item in admin notices.
     */
    public function displayTitle(): string
    {
        return (string) ($this->title ?? $this->name ?? '#'.$this->getKey());
    }
}
