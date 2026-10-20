<?php

namespace App\Http\Controllers\Site;

use App\Enums\ProjectType;
use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\Service;
use App\Models\Technology;
use App\Services\RichContent;
use App\Support\Seo\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class WorkController extends Controller
{
    public function __construct(protected RichContent $content) {}

    public function index(Request $request): View
    {
        $filters = [
            'category' => $request->string('category')->limit(120, '')->toString() ?: null,
            'service' => $request->string('service')->limit(120, '')->toString() ?: null,
            'technology' => $request->string('technology')->limit(120, '')->toString() ?: null,
            'type' => in_array($request->query('type'), ProjectType::values(), true) ? $request->query('type') : null,
        ];

        $projects = Project::query()
            ->published()
            ->with(['cover', 'category', 'services'])
            ->when($filters['category'], fn (Builder $query, string $slug) => $query->whereHas('category', fn (Builder $query) => $query->where('slug', $slug)))
            ->when($filters['service'], fn (Builder $query, string $slug) => $query->whereHas('services', fn (Builder $query) => $query->where('slug', $slug)))
            ->when($filters['technology'], fn (Builder $query, string $slug) => $query->whereHas('technologies', fn (Builder $query) => $query->where('slug', $slug)))
            ->when($filters['type'], fn (Builder $query, string $type) => $query->where('project_type', $type))
            ->ordered()
            ->paginate(12)
            ->withQueryString();

        $page = Page::system('work');

        return view('site.work.index', [
            'page' => $page,
            'projects' => $projects,
            'filters' => $filters,
            'hasFilters' => array_filter($filters) !== [],
            'categories' => ProjectCategory::query()->whereHas('projects', fn (Builder $query) => $query->published())->orderBy('sort_order')->get(),
            'services' => Service::query()->published()->whereHas('projects', fn (Builder $query) => $query->published())->ordered()->get(['id', 'title', 'slug']),
            'technologies' => Technology::query()->whereHas('projects', fn (Builder $query) => $query->published())->orderBy('name')->get(),
            'types' => ProjectType::options(),
            'seo' => Seo::make($page?->title ?? 'Work')
                ->description($page?->excerpt)
                ->meta($page?->seo)
                ->noindex(array_filter($filters) !== [])
                ->canonical(route('work.index').($projects->currentPage() > 1 && array_filter($filters) === [] ? '?page='.$projects->currentPage() : ''))
                ->breadcrumbs([
                    ['name' => 'Home', 'url' => route('home')],
                    ['name' => 'Work', 'url' => route('work.index')],
                ]),
        ]);
    }

    public function show(Project $project): View
    {
        abort_unless($project->isPubliclyVisible(), 404);

        return $this->render($project);
    }

    public function render(Project $project, bool $preview = false): View
    {
        $project->load([
            'cover', 'category', 'seo.ogImage', 'technologies', 'mediaAttachments.media',
            'services' => fn ($query) => $query->published()->ordered(),
            'products' => fn ($query) => $query->published()->with('logo'),
        ]);

        $next = Project::query()->published()->ordered()->whereKeyNot($project->id)
            ->with(['cover', 'category', 'services'])
            ->where('sort_order', '>=', $project->sort_order)
            ->first()
            ?? Project::query()->published()->ordered()->whereKeyNot($project->id)->with(['cover', 'category', 'services'])->first();

        $sections = collect(['challenge' => 'The challenge', 'objectives' => 'Objectives', 'approach' => 'Process & approach', 'solution' => 'The solution', 'role' => 'Our role', 'results' => 'Results & outcomes'])
            ->map(fn (string $label, string $field) => ['label' => $label, 'html' => $this->content->prepare($project->{$field})['html']])
            ->filter(fn (array $section) => trim(strip_tags($section['html'], '<img><iframe>')) !== '');

        return view('site.work.show', [
            'project' => $project,
            'contentSections' => $sections,
            'gallery' => $project->mediaCollection('gallery'),
            'videos' => $project->videoEmbeds(),
            'next' => $next,
            'isPreview' => $preview,
            'seo' => Seo::make($project->title)
                ->description($project->summary)
                ->image($project->cover)
                ->meta($project->seo)
                ->type('article')
                ->noindex($preview)
                ->canonical($preview ? null : route('work.show', $project))
                ->breadcrumbs([
                    ['name' => 'Home', 'url' => route('home')],
                    ['name' => 'Work', 'url' => route('work.index')],
                    ['name' => $project->title, 'url' => route('work.show', $project)],
                ]),
        ]);
    }
}
