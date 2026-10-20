<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\JobListing;
use App\Models\Page;
use App\Models\Product;
use App\Models\Project;
use App\Models\Service;
use App\Models\TeamMember;
use App\Services\SiteSettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * XML sitemap and robots.txt. Only published, indexable content is listed.
 */
class SitemapController extends Controller
{
    public const CACHE_KEY = 'sitemap.urls';

    public function sitemap(SiteSettings $settings): Response
    {
        $urls = $settings->get('seo.discourage_indexing') ? [] : Cache::remember(self::CACHE_KEY, 600, fn () => $this->urls());

        return response()
            ->view('site.sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots(SiteSettings $settings): Response
    {
        $lines = ['User-agent: *'];

        if ($settings->get('seo.discourage_indexing')) {
            $lines[] = 'Disallow: /';
        } else {
            $lines[] = 'Disallow: /admin';
            $lines[] = 'Disallow: /preview';
            $lines[] = 'Disallow: /search';
            $lines[] = 'Disallow: /contact/thank-you';
            $lines[] = 'Disallow: /careers/application-received';
            $lines[] = 'Disallow: /newsletter/';
            $lines[] = 'Disallow: /onboarding/';

            foreach (preg_split('/\R/', (string) $settings->get('seo.robots_extra')) ?: [] as $line) {
                $line = trim($line);

                if (preg_match('/^(Allow|Disallow|Crawl-delay):\s*\S*/i', $line)) {
                    $lines[] = $line;
                }
            }
        }

        $lines[] = '';
        $lines[] = 'Sitemap: '.route('sitemap');

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /**
     * @return list<array{loc: string, lastmod: string|null}>
     */
    protected function urls(): array
    {
        $indexable = fn (Builder $query) => $query->whereDoesntHave('seo', fn (Builder $query) => $query->where('noindex', true));
        $urls = [];

        $systemPages = Page::query()->whereNotNull('system_key')->with('seo')->get()->keyBy('system_key');

        foreach (Page::SYSTEM_ROUTES as $key => $route) {
            $page = $systemPages->get($key);

            if ($page?->seo?->noindex) {
                continue;
            }

            $urls[] = ['loc' => route($route), 'lastmod' => $page?->updated_at?->toAtomString()];
        }

        if (TeamMember::query()->where('is_founder', true)->published()->exists()) {
            $urls[] = ['loc' => route('about.founder'), 'lastmod' => null];
        }

        $collections = [
            [Service::class, 'services.show'],
            [Project::class, 'work.show'],
            [Product::class, 'products.show'],
            [Article::class, 'insights.show'],
        ];

        foreach ($collections as [$class, $route]) {
            $class::query()->published()->tap($indexable)->get(['id', 'slug', 'updated_at'])
                ->each(function ($item) use (&$urls, $route): void {
                    $urls[] = ['loc' => route($route, $item), 'lastmod' => $item->updated_at?->toAtomString()];
                });
        }

        JobListing::query()->open()->tap($indexable)->get(['id', 'slug', 'updated_at'])
            ->each(function (JobListing $job) use (&$urls): void {
                $urls[] = ['loc' => route('careers.show', $job), 'lastmod' => $job->updated_at?->toAtomString()];
            });

        ArticleCategory::query()->whereHas('articles', fn (Builder $query) => $query->published())->get(['id', 'slug', 'updated_at'])
            ->each(function (ArticleCategory $category) use (&$urls): void {
                $urls[] = ['loc' => route('insights.category', $category), 'lastmod' => null];
            });

        Page::query()->published()->whereNull('system_key')->tap($indexable)->get(['id', 'slug', 'updated_at'])
            ->each(function (Page $page) use (&$urls): void {
                $urls[] = ['loc' => route('pages.show', $page), 'lastmod' => $page->updated_at?->toAtomString()];
            });

        return $urls;
    }
}
