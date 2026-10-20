<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['parent_id', 'label', 'url', 'description', 'open_in_new_tab', 'is_enabled', 'sort_order'])]
class NavigationItem extends Model
{
    protected function casts(): array
    {
        return [
            'open_in_new_tab' => 'boolean',
            'is_enabled' => 'boolean',
        ];
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(NavigationMenu::class, 'navigation_menu_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function href(): string
    {
        return Str::startsWith($this->url, ['http://', 'https://', 'mailto:', 'tel:', '#'])
            ? $this->url
            : url($this->url);
    }

    public function isExternal(): bool
    {
        return Str::startsWith($this->url, ['http://', 'https://'])
            && ! Str::startsWith($this->url, rtrim(config('app.url'), '/'));
    }

    /**
     * Whether the item (or one of its children) matches the current request path.
     */
    public function isActive(): bool
    {
        if (Str::startsWith($this->url, ['http', 'mailto:', 'tel:', '#'])) {
            return false;
        }

        $path = trim($this->url, '/');
        $current = trim(request()->path(), '/');

        if ($path === '') {
            return $current === '';
        }

        return $current === $path || Str::startsWith($current, $path.'/');
    }
}
