<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Service;
use App\Services\RichContent;
use App\Support\Sections\SectionDataResolver;
use App\Support\Seo\Seo;
use App\Support\VideoEmbed;
use Illuminate\Contracts\View\View;

class ServiceController extends Controller
{
    public function __construct(protected RichContent $content, protected SectionDataResolver $resolver) {}

    public function index(): View
    {
        $page = Page::system('services');
        $sections = $page ? $page->enabledSections()->get() : collect();

        return view('site.services.index', [
            'page' => $page,
            'bodyClass' => 'has-overlay-header',
            'services' => Service::query()->published()->ordered()->with(['heroImage', 'loopVideo'])->get(),
            'sections' => $sections,
            'sectionData' => $this->resolver->resolve($sections),
            'seo' => Seo::make($page?->title ?? 'Services')
                ->description($page?->excerpt)
                ->meta($page?->seo)
                ->breadcrumbs([
                    ['name' => 'Home', 'url' => route('home')],
                    ['name' => 'Services', 'url' => route('services.index')],
                ]),
        ]);
    }

    public function show(Service $service): View
    {
        abort_unless($service->isPubliclyVisible(), 404);

        return $this->render($service);
    }

    public function render(Service $service, bool $preview = false): View
    {
        $service->load([
            'heroImage',
            'seo.ogImage',
            'projects' => fn ($query) => $query->published()->with(['cover', 'category', 'services'])->ordered()->limit(3),
            'products' => fn ($query) => $query->published()->with(['logo', 'heroImage'])->ordered(),
            'faqs' => fn ($query) => $query->published(),
        ]);

        $otherServices = Service::query()->published()->ordered()->whereKeyNot($service->id)->get(['id', 'title', 'slug', 'icon', 'short_description']);

        return view('site.services.show', [
            'service' => $service,
            'body' => $this->content->prepare($service->body)['html'],
            'video' => VideoEmbed::fromUrl($service->hero_video_url),
            'otherServices' => $otherServices,
            'isPreview' => $preview,
            'seo' => Seo::make($service->title)
                ->description($service->short_description)
                ->image($service->heroImage)
                ->meta($service->seo)
                ->noindex($preview)
                ->canonical($preview ? null : route('services.show', $service))
                ->breadcrumbs([
                    ['name' => 'Home', 'url' => route('home')],
                    ['name' => 'Services', 'url' => route('services.index')],
                    ['name' => $service->title, 'url' => route('services.show', $service)],
                ])
                ->schema(array_filter([
                    '@context' => 'https://schema.org',
                    '@type' => 'Service',
                    'name' => $service->title,
                    'description' => $service->short_description,
                    'provider' => ['@id' => url('/').'#organization'],
                    'areaServed' => 'Worldwide',
                    'url' => route('services.show', $service),
                ])),
        ]);
    }
}
