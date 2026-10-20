<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'slug'])]
class Technology extends Model
{
    use HasSlug;

    public function slugSourceAttribute(): string
    {
        return 'name';
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class);
    }

    /**
     * Resolve a comma separated list of technology names to IDs, creating missing entries.
     *
     * @return list<int>
     */
    public static function idsFromNames(?string $names): array
    {
        return collect(explode(',', (string) $names))
            ->map(fn (string $name) => Str::of($name)->squish()->limit(60, '')->toString())
            ->filter()
            ->unique(fn (string $name) => Str::lower($name))
            ->map(fn (string $name) => static::query()->where('slug', Str::slug($name))->first()
                ?? static::query()->create(['name' => $name]))
            ->pluck('id')
            ->all();
    }
}
