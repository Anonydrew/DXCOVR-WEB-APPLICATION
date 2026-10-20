<?php

namespace App\Services;

use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Loads enabled navigation menus as plain arrays (cache-friendly) keyed by location.
 */
class Navigation
{
    public const CACHE_KEY = 'navigation.menus';

    /**
     * Menus resolved during the current request.
     *
     * @var array<string, list<array<string, mixed>>>|null
     */
    protected ?array $resolved = null;

    /**
     * @return array<string, list<array{label: string, url: string, href: string, description: string|null, new_tab: bool, external: bool, children: list<array<string, mixed>>}>>
     */
    public function menus(): array
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }

        try {
            return $this->resolved = Cache::rememberForever(self::CACHE_KEY, function (): array {
                if (! Schema::hasTable('navigation_menus')) {
                    return [];
                }

                // Links to custom pages that are not live are hidden rather than leading to a 404.
                $hiddenPaths = Page::query()->whereNull('system_key')->get(['id', 'slug', 'status', 'published_at'])
                    ->reject(fn (Page $page) => $page->isPubliclyVisible())
                    ->map(fn (Page $page) => '/'.$page->slug)
                    ->all();

                return NavigationMenu::query()
                    ->with(['items' => fn ($query) => $query->where('is_enabled', true)->orderBy('sort_order')])
                    ->get()
                    ->mapWithKeys(fn (NavigationMenu $menu) => [$menu->key => $this->tree(
                        $menu->items->reject(fn (NavigationItem $item) => in_array(rtrim($item->url, '/'), $hiddenPaths, true)),
                    )])
                    ->all();
            });
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function menu(string $key): array
    {
        return $this->menus()[$key] ?? [];
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->resolved = null;
    }

    /**
     * @param  Collection<int, NavigationItem>  $items
     * @return list<array<string, mixed>>
     */
    protected function tree(Collection $items, ?int $parentId = null): array
    {
        return $items
            ->filter(fn (NavigationItem $item) => $item->parent_id === $parentId)
            ->map(fn (NavigationItem $item) => [
                'label' => $item->label,
                'url' => $item->url,
                'href' => $item->href(),
                'description' => $item->description,
                'new_tab' => $item->open_in_new_tab,
                'external' => $item->isExternal(),
                'children' => $parentId === null ? $this->tree($items, $item->id) : [],
            ])
            ->values()
            ->all();
    }

    /**
     * Whether a navigation URL matches the current request (or one of its children).
     *
     * @param  array<string, mixed>  $item
     */
    public static function isActive(array $item): bool
    {
        $url = (string) $item['url'];

        if (str_starts_with($url, 'http') || str_starts_with($url, 'mailto:') || str_starts_with($url, 'tel:') || str_starts_with($url, '#')) {
            return false;
        }

        $path = trim($url, '/');
        $current = trim(request()->path(), '/');

        if ($path === '') {
            return $current === '';
        }

        if ($current === $path || str_starts_with($current, $path.'/')) {
            return true;
        }

        foreach ($item['children'] ?? [] as $child) {
            if (self::isActive($child)) {
                return true;
            }
        }

        return false;
    }
}
