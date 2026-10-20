<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\JobListing;
use App\Models\Page;
use App\Models\Product;
use App\Models\Project;
use App\Models\Service;
use App\Support\Seo\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SearchController extends Controller
{
    protected const PER_GROUP = 8;

    public function __invoke(Request $request): View
    {
        $term = Str::of((string) $request->query('q'))->squish()->limit(100, '')->toString();
        $escaped = addcslashes($term, '%_\\');
        $groups = [];

        if (mb_strlen($term) >= 2) {
            $groups = [
                'Services' => Service::query()->published()->search($escaped)->ordered()->limit(self::PER_GROUP)->get()
                    ->map(fn (Service $item) => ['title' => $item->title, 'url' => route('services.show', $item), 'excerpt' => $item->short_description, 'meta' => 'Service']),
                'Work' => Project::query()->published()->search($escaped)->with('category')->ordered()->limit(self::PER_GROUP)->get()
                    ->map(fn (Project $item) => ['title' => $item->title, 'url' => route('work.show', $item), 'excerpt' => $item->summary, 'meta' => $item->category?->name ?? 'Case study']),
                'Products' => Product::query()->published()->search($escaped)->ordered()->limit(self::PER_GROUP)->get()
                    ->map(fn (Product $item) => ['title' => $item->name, 'url' => route('products.show', $item), 'excerpt' => $item->short_description, 'meta' => $item->product_status->label()]),
                'Insights' => Article::query()->published()->search($escaped)->latest('published_at')->limit(self::PER_GROUP)->get()
                    ->map(fn (Article $item) => ['title' => $item->title, 'url' => route('insights.show', $item), 'excerpt' => $item->excerpt, 'meta' => $item->published_at?->format('j M Y')]),
                'Careers' => JobListing::query()->open()->where(fn (Builder $query) => $query->where('title', 'like', "%{$escaped}%")->orWhere('summary', 'like', "%{$escaped}%"))->limit(self::PER_GROUP)->get()
                    ->map(fn (JobListing $item) => ['title' => $item->title, 'url' => route('careers.show', $item), 'excerpt' => $item->summary, 'meta' => $item->location]),
                'Pages' => Page::query()->published()->whereNull('system_key')->where(fn (Builder $query) => $query->where('title', 'like', "%{$escaped}%")->orWhere('body', 'like', "%{$escaped}%"))->limit(self::PER_GROUP)->get()
                    ->map(fn (Page $item) => ['title' => $item->title, 'url' => route('pages.show', $item), 'excerpt' => $item->excerpt, 'meta' => 'Page']),
            ];

            $groups = array_filter($groups, fn ($items) => $items->isNotEmpty());
        }

        return view('site.search', [
            'term' => $term,
            'groups' => $groups,
            'total' => collect($groups)->sum(fn ($items) => $items->count()),
            'seo' => Seo::make($term !== '' ? 'Search: '.$term : 'Search')->noindex(),
        ]);
    }
}
