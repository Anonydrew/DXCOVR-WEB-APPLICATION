<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Generates a unique, URL-safe slug from the model's title (or name) when one is not supplied.
 */
trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::saving(function ($model): void {
            $source = $model->slug ?: $model->{$model->slugSourceAttribute()};
            $slug = Str::slug((string) $source);

            if ($slug === '') {
                $slug = Str::lower(Str::random(8));
            }

            if ($model->isDirty('slug') || ! $model->exists || $model->slug === null || $model->slug === '') {
                $model->slug = static::uniqueSlug($slug, $model->getKey());
            }
        });
    }

    public function slugSourceAttribute(): string
    {
        return 'title';
    }

    /**
     * Return the given slug, suffixed with a counter when it is already in use.
     */
    public static function uniqueSlug(string $slug, int|string|null $ignoreId = null): string
    {
        $slug = Str::limit($slug, 180, '');
        $candidate = $slug;
        $counter = 2;

        while (static::query()
            ->where('slug', $candidate)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $candidate = $slug.'-'.$counter++;
        }

        return $candidate;
    }
}
