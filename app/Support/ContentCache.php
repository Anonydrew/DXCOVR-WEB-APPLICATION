<?php

namespace App\Support;

use App\Http\Controllers\Site\SitemapController;
use App\Services\Navigation;
use Illuminate\Support\Facades\Cache;

/**
 * Clears caches derived from published content (navigation and sitemap).
 */
final class ContentCache
{
    public static function flush(): void
    {
        app(Navigation::class)->flush();
        Cache::forget(SitemapController::CACHE_KEY);
    }
}
