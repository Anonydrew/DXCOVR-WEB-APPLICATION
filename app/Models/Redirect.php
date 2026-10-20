<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

#[Fillable(['from_path', 'to_url', 'status_code', 'is_active', 'notes'])]
class Redirect extends Model
{
    public const CACHE_KEY = 'redirects.map';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'status_code' => 'integer',
            'last_hit_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /**
     * Normalise a request path to the stored form: leading slash, no trailing slash, no query.
     */
    public static function normalisePath(string $path): string
    {
        $path = parse_url($path, PHP_URL_PATH) ?: '/';

        return '/'.trim($path, '/');
    }

    /**
     * @return array<string, array{id: int, to: string, code: int}>
     */
    public static function activeMap(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => static::query()
            ->where('is_active', true)
            ->get(['id', 'from_path', 'to_url', 'status_code'])
            ->mapWithKeys(fn (self $redirect) => [
                $redirect->from_path => ['id' => $redirect->id, 'to' => $redirect->to_url, 'code' => $redirect->status_code],
            ])
            ->all());
    }
}
