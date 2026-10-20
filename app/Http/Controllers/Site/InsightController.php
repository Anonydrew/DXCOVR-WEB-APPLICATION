<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Page;
use App\Models\Tag;
use App\Services\RichContent;
use App\Services\SiteSettings;
use App\Support\Seo\Seo;
use App\Support\Seo\StructuredData;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class InsightController extends Controller
{
    public function __construct(protected RichContent $content) {}

    public function index(Request $request): View|RedirectResponse
    {
        // The "All collections" dropdown submits ?category=slug; send it to the category page.
        if ($request->filled('category')) {
            $category = ArticleCategory::query()->where('slug', (string) $request->query('category'))->first();

            return $category ? redirect()->route('insights.category', $category) : redirect()->route('insights.index');
        }

        $page = Page::system('insights');
        $firstPage = (int) $request->query('page', 1) <= 1;

        $hero = $firstPage
            ? ($this->listing()->where('is_featured', true)->first() ?? $this->listing()->first())
            : null;

        $editorsPick = $hero
            ? $this->listing()->where('is_featured', true)->whereKeyNot($hero->id)->limit(4)->get()
            : collect();

        $articles = $this->listing()
            ->when($hero, fn (Builder $query) => $query->whereKeyNot($hero->id))
            ->paginate(8)
            ->fragment('recent');

        return view('site.insights.index', [
            'page' => $page,
            'heroPage' => $page,
            'hero' => $hero,
            'editorsPick' => $editorsPick,
            'bodyClass' => $hero || ($firstPage && $articles->isEmpty()) ? 'has-overlay-header' : '',
            'heading' => $page?->title ?? 'Insights',
            'lede' => $page?->excerpt,
            'articles' => $articles,
            'categories' => $this->categories(),
            'activeCategory' => null,
            'seo' => Seo::make($page?->title ?? 'Insights')
                ->description($page?->excerpt)
                ->meta($page?->seo)
                ->breadcrumbs([
                    ['name' => 'Home', 'url' => route('home')],
                    ['name' => 'Insights', 'url' => route('insights.index')],
                ]),
        ]);
    }

    public function category(ArticleCategory $category): View
    {
        $articles = $this->listing()
            ->whereHas('categories', fn (Builder $query) => $query->whereKey($category->id))
            ->paginate(8);

        return view('site.insights.index', [
            'page' => null,
            'heroPage' => null,
            'hero' => null,
            'editorsPick' => collect(),
            'heading' => $category->name,
            'lede' => $category->description,
            'articles' => $articles,
            'categories' => $this->categories(),
            'activeCategory' => $category,
            'seo' => Seo::make($category->name.' · Insights')
                ->description($category->description ?: 'Articles about '.$category->name.' from DXCOVR.')
                ->breadcrumbs([
                    ['name' => 'Home', 'url' => route('home')],
                    ['name' => 'Insights', 'url' => route('insights.index')],
                    ['name' => $category->name, 'url' => route('insights.category', $category)],
                ]),
        ]);
    }

    public function tag(Tag $tag): View
    {
        $articles = $this->listing()
            ->whereHas('tags', fn (Builder $query) => $query->whereKey($tag->id))
            ->paginate(8);

        return view('site.insights.index', [
            'page' => null,
            'heroPage' => null,
            'hero' => null,
            'editorsPick' => collect(),
            'heading' => '#'.$tag->name,
            'lede' => 'Articles tagged “'.$tag->name.'”.',
            'articles' => $articles,
            'categories' => $this->categories(),
            'activeCategory' => null,
            'seo' => Seo::make('Articles tagged '.$tag->name)
                ->noindex($articles->total() < 2)
                ->breadcrumbs([
                    ['name' => 'Home', 'url' => route('home')],
                    ['name' => 'Insights', 'url' => route('insights.index')],
                    ['name' => '#'.$tag->name, 'url' => route('insights.tag', $tag)],
                ]),
        ]);
    }

    public function show(Article $article): View
    {
        abort_unless($article->isPubliclyVisible(), 404);

        return $this->render($article);
    }

    public function render(Article $article, bool $preview = false): View
    {
        $article->load(['featuredImage', 'author.avatar', 'categories', 'tags', 'seo.ogImage']);
        $prepared = $this->content->prepare($article->body);

        $recent = $this->listing()->whereKeyNot($article->id)->limit(4)->get();

        return view('site.insights.show', [
            'article' => $article,
            'body' => $prepared['html'],
            'toc' => $article->show_toc && count($prepared['toc']) >= 2 ? $prepared['toc'] : [],
            'recent' => $recent,
            'isPreview' => $preview,
            'seo' => Seo::make($article->title)
                ->description($article->excerpt)
                ->image($article->featuredImage)
                ->meta($article->seo)
                ->type('article')
                ->times($article->published_at, $article->updated_at)
                ->noindex($preview)
                ->canonical($preview ? null : route('insights.show', $article))
                ->breadcrumbs([
                    ['name' => 'Home', 'url' => route('home')],
                    ['name' => 'Insights', 'url' => route('insights.index')],
                    ['name' => $article->title, 'url' => route('insights.show', $article)],
                ])
                ->schema(StructuredData::article($article)),
        ]);
    }

    public function feed(SiteSettings $settings): Response
    {
        $articles = Article::query()->published()->with(['categories', 'author'])->latest('published_at')->limit(30)->get();

        return response()
            ->view('site.insights.feed', ['articles' => $articles, 'site' => $settings])
            ->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }

    protected function listing(): Builder
    {
        return Article::query()->published()->with(['featuredImage', 'categories', 'author'])->latest('published_at');
    }

    /**
     * @return Collection<int, ArticleCategory>
     */
    protected function categories(): Collection
    {
        return ArticleCategory::query()
            ->whereHas('articles', fn (Builder $query) => $query->published())
            ->orderBy('sort_order')
            ->get();
    }
}
