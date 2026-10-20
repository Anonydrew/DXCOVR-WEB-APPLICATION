<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Page;
use App\Models\TeamMember;
use App\Services\RichContent;
use App\Support\Sections\SectionDataResolver;
use App\Support\Seo\Seo;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    public function __construct(protected SectionDataResolver $resolver, protected RichContent $content) {}

    public function about(): View
    {
        $page = Page::system('about');
        $sections = $page ? $page->enabledSections()->get() : collect();

        return view('site.pages.about', [
            'page' => $page,
            'sections' => $sections,
            'sectionData' => $this->resolver->resolve($sections),
            'seo' => Seo::make($page?->title ?? 'About')
                ->description($page?->excerpt)
                ->meta($page?->seo)
                ->breadcrumbs([
                    ['name' => 'Home', 'url' => route('home')],
                    ['name' => 'About', 'url' => route('about')],
                ]),
        ]);
    }

    public function founder(): View
    {
        $founder = TeamMember::query()
            ->where('is_founder', true)
            ->published()
            ->with(['photo', 'projects' => fn ($query) => $query->published()->with(['cover', 'category', 'services'])->ordered()])
            ->firstOrFail();

        $articles = $founder->user_id
            ? Article::query()->published()->where('author_id', $founder->user_id)->with(['featuredImage', 'categories'])->latest('published_at')->limit(3)->get()
            : collect();

        return view('site.pages.founder', [
            'founder' => $founder,
            'bio' => $this->content->prepare($founder->bio)['html'],
            'articles' => $articles,
            'seo' => Seo::make($founder->name.($founder->role_title ? ', '.$founder->role_title : ''))
                ->description($founder->short_bio)
                ->image($founder->photo)
                ->type('profile')
                ->breadcrumbs([
                    ['name' => 'Home', 'url' => route('home')],
                    ['name' => 'About', 'url' => route('about')],
                    ['name' => $founder->name, 'url' => route('about.founder')],
                ])
                ->schema(array_filter([
                    '@context' => 'https://schema.org',
                    '@type' => 'Person',
                    'name' => $founder->name,
                    'jobTitle' => $founder->role_title,
                    'description' => $founder->short_bio,
                    'image' => $founder->photo?->url(),
                    'worksFor' => ['@id' => url('/').'#organization'],
                    'sameAs' => collect($founder->links ?? [])->pluck('url')->filter()->values()->all() ?: null,
                ])),
        ]);
    }

    public function show(Page $page): View
    {
        abort_if($page->isSystem() || ! $page->isPubliclyVisible(), 404);

        return $this->render($page);
    }

    public function render(Page $page, bool $preview = false): View
    {
        $page->loadMissing('seo.ogImage');
        $sections = $page->enabledSections()->get();
        $prepared = $this->content->prepare($page->body);

        return view('site.pages.show', [
            'page' => $page,
            'body' => $prepared['html'],
            'toc' => $prepared['toc'],
            'sections' => $sections,
            'sectionData' => $this->resolver->resolve($sections),
            'isPreview' => $preview,
            'seo' => Seo::make($page->title)
                ->description($page->excerpt)
                ->meta($page->seo)
                ->noindex($preview)
                ->canonical($preview ? null : route('pages.show', $page))
                ->breadcrumbs([
                    ['name' => 'Home', 'url' => route('home')],
                    ['name' => $page->title, 'url' => route('pages.show', $page)],
                ]),
        ]);
    }
}
