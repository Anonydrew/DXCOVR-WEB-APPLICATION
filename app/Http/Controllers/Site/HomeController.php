<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Support\Sections\SectionDataResolver;
use App\Support\Seo\Seo;
use App\Support\Seo\StructuredData;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(SectionDataResolver $resolver): View
    {
        $page = Page::system('home');
        $sections = $page ? $page->enabledSections()->get() : collect();

        $seo = Seo::make($page?->title)
            ->description($page?->excerpt)
            ->meta($page?->seo)
            ->canonical(route('home'))
            ->schema(StructuredData::organization())
            ->schema(StructuredData::website());

        return view('site.home', [
            'page' => $page,
            'sections' => $sections,
            'sectionData' => $resolver->resolve($sections),
            'seo' => $seo,
            // The full-bleed hero sits behind a transparent header.
            'bodyClass' => $sections->first()?->type === 'hero' ? 'has-overlay-header' : '',
        ]);
    }
}
