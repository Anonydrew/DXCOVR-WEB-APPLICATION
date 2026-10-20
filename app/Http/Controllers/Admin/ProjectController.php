<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProjectType;
use App\Http\Requests\Admin\ProjectRequest;
use App\Models\Product;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\Service;
use App\Models\TeamMember;
use App\Models\Technology;
use App\Services\HtmlSanitizer;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectController extends AdminController
{
    protected const RICH_FIELDS = ['challenge', 'objectives', 'approach', 'solution', 'role', 'results'];

    public function __construct(protected HtmlSanitizer $sanitizer) {}

    public function index(Request $request): View
    {
        $projects = Project::query()
            ->with(['category', 'cover'])
            ->withStatus($request->query('status'))
            ->when($request->query('type'), fn (Builder $query, $type) => $query->where('project_type', $type))
            ->when($request->query('category'), fn (Builder $query, $id) => $query->where('project_category_id', $id))
            ->search($request->string('q')->limit(100, '')->toString() ?: null)
            ->orderBy('sort_order')
            ->latest('updated_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.projects.index', [
            'projects' => $projects,
            'categories' => ProjectCategory::query()->orderBy('sort_order')->pluck('name', 'id'),
            'canManage' => $request->user()->can('projects.manage'),
            'isFiltered' => $request->hasAny(['q', 'status', 'type', 'category']),
        ]);
    }

    public function create(): View
    {
        return view('admin.projects.form', $this->formData(new Project(['project_type' => ProjectType::Client])));
    }

    public function store(ProjectRequest $request): RedirectResponse
    {
        $project = new Project;
        $project->sort_order = (int) Project::query()->max('sort_order') + 1;

        $warning = DB::transaction(function () use ($request, $project) {
            $this->fill($project, $request);
            $project->save();
            $this->syncRelations($project, $request);

            return $this->saveSeo($project, $request->validated('seo'));
        });

        $this->audit()->record('content.created', $project, 'Created project “'.$project->title.'”');

        return $this->saved('admin.projects.edit', [$project], 'Case study saved as a draft.', $warning);
    }

    public function edit(Project $project): View
    {
        $project->load(['cover', 'seo.ogImage', 'services', 'products', 'technologies', 'teamMembers', 'mediaAttachments.media', 'editor']);

        return view('admin.projects.form', $this->formData($project));
    }

    public function update(ProjectRequest $request, Project $project): RedirectResponse
    {
        $warning = DB::transaction(function () use ($request, $project) {
            $this->fill($project, $request);
            $project->save();
            $this->syncRelations($project, $request);

            return $this->saveSeo($project, $request->validated('seo'));
        });

        $this->audit()->record('content.updated', $project, 'Updated project “'.$project->title.'”', $this->audit()->changes($project));

        return $this->saved('admin.projects.edit', [$project], 'Case study updated.', $warning);
    }

    public function destroy(Project $project): RedirectResponse
    {
        $title = $project->title;

        DB::transaction(function () use ($project): void {
            $project->mediaAttachments()->delete();
            $project->delete();
        });

        $this->audit()->record('content.deleted', null, 'Deleted project “'.$title.'”');

        return redirect()->route('admin.projects.index')->with('status', 'Case study deleted.');
    }

    protected function fill(Project $project, ProjectRequest $request): void
    {
        $project->fill($request->safe()->only([
            'title', 'slug', 'summary', 'tagline', 'project_category_id', 'project_type', 'delivery_status', 'client_name',
            'anonymised_client_label', 'duration', 'year', 'cover_media_id', 'external_url',
        ]));

        foreach (self::RICH_FIELDS as $field) {
            $project->{$field} = $this->sanitizer->rich($request->validated($field));
        }

        $project->is_confidential = $request->boolean('is_confidential');
        $project->is_featured = $request->boolean('is_featured');
        $project->features = $request->validated('features') ?: null;
        $project->video_urls = $request->validated('video_urls') ?: null;
    }

    protected function syncRelations(Project $project, ProjectRequest $request): void
    {
        $project->services()->sync($request->validated('services') ?? []);
        $project->products()->sync($request->validated('products') ?? []);
        $project->teamMembers()->sync($request->validated('team_members') ?? []);
        $project->technologies()->sync(Technology::idsFromNames($request->validated('technologies')));
        $project->syncMediaCollection('gallery', $request->validated('gallery') ?? []);
    }

    /**
     * @return array<string, mixed>
     */
    protected function formData(Project $project): array
    {
        return [
            'project' => $project,
            'categories' => ProjectCategory::query()->orderBy('sort_order')->pluck('name', 'id'),
            'services' => Service::query()->ordered()->pluck('title', 'id'),
            'products' => Product::query()->ordered()->pluck('name', 'id'),
            'teamMembers' => TeamMember::query()->orderBy('sort_order')->pluck('name', 'id'),
            'technologySuggestions' => Technology::query()->orderBy('name')->pluck('name'),
        ];
    }
}
