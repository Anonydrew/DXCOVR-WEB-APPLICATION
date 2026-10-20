<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'slug'])]
class Tag extends Model
{
    /** @use HasFactory<TagFactory> */
    use HasFactory, HasSlug;

    public function slugSourceAttribute(): string
    {
        return 'name';
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class);
    }

    /**
     * Resolve a comma separated list of tag names to tag IDs, creating missing tags.
     *
     * @return list<int>
     */
    public static function idsFromNames(?string $names): array
    {
        return collect(explode(',', (string) $names))
            ->map(fn (string $name) => Str::of($name)->squish()->limit(50, '')->toString())
            ->filter()
            ->unique(fn (string $name) => Str::lower($name))
            ->map(fn (string $name) => static::query()->where('slug', Str::slug($name))->first()
                ?? static::query()->create(['name' => $name]))
            ->pluck('id')
            ->all();
    }
}
