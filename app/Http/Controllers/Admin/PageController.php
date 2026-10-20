<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\PageRequest;
use App\Models\Page;
use App\Services\HtmlSanitizer;
use App\Support\Sections\SectionRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class PageController extends AdminController
{
    public function __construct(protected HtmlSanitizer $sanitizer) {}

    public function index(): View
    {
        return view('admin.pages.index', [
            'systemPages' => Page::query()->whereNotNull('system_key')->withCount('sections')->orderByRaw("CASE system_key WHEN 'home' THEN 0 ELSE 1 END")->orderBy('title')->get(),
            'pages' => Page::query()->whereNull('system_key')->orderBy('template')->orderBy('title')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.pages.form', [
            'page' => new Page(['template' => 'standard']),
            'sectionTypes' => SectionRegistry::options(),
        ]);
    }

    public function store(PageRequest $request): RedirectResponse
    {
        $page = new Page;

        $warning = DB::transaction(function () use ($request, $page) {
            $page->fill($request->safe()->only(['title', 'slug', 'template', 'excerpt']));
            $page->body = $this->sanitizer->rich($request->validated('body'));
            $page->save();

            return $this->saveSeo($page, $request->validated('seo'));
        });

        $this->audit()->record('content.created', $page, 'Created page “'.$page->title.'”');

        return $this->saved('admin.pages.edit', [$page], 'Page saved as a draft.', $warning);
    }

    public function edit(Page $page): View
    {
        $page->load(['sections', 'seo.ogImage', 'editor', 'heroImage']);

        return view('admin.pages.form', [
            'page' => $page,
            'sectionTypes' => SectionRegistry::options(),
        ]);
    }

    public function update(PageRequest $request, Page $page): RedirectResponse
    {
        $warning = DB::transaction(function () use ($request, $page) {
            $page->fill($request->safe()->only($page->isSystem() ? ['title', 'excerpt'] : ['title', 'slug', 'template', 'excerpt']));

            if ($page->hasFeatureHero()) {
                $page->fill($request->safe()->only(['hero_media_id', 'hero_eyebrow', 'hero_cta_label', 'hero_cta_url']));
            }

            if (! $page->isSystem()) {
                $page->body = $this->sanitizer->rich($request->validated('body'));
            }

            $page->save();

            return $this->saveSeo($page, $request->validated('seo'));
        });

        $this->audit()->record('content.updated', $page, 'Updated page “'.$page->title.'”', $this->audit()->changes($page));

        return $this->saved('admin.pages.edit', [$page], 'Page saved.', $warning);
    }

    public function destroy(Page $page): RedirectResponse
    {
        abort_if($page->isSystem(), 403, 'System pages cannot be deleted.');

        $title = $page->title;
        $page->delete();

        $this->audit()->record('content.deleted', null, 'Deleted page “'.$title.'”');

        return redirect()->route('admin.pages.index')->with('status', 'Page deleted. Consider adding a redirect if it was linked from elsewhere.');
    }
}
