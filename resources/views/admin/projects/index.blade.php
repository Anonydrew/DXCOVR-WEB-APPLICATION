@extends('layouts.admin')

@section('title', 'Portfolio')

@section('content')
    <x-admin.page-header title="Portfolio" description="Case studies, internal products, prototypes and concepts, each clearly labelled on the website.">
        <x-slot:actions>
            @if ($canManage)
                <a href="{{ route('admin.project-categories.index') }}" class="a-btn">Categories</a>
                <a href="{{ route('admin.technologies.index') }}" class="a-btn">Technologies</a>
                <a href="{{ route('admin.projects.create') }}" class="a-btn a-btn-primary"><x-icon name="plus" :size="16" /> New case study</a>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <div class="a-card overflow-hidden">
        <x-admin.filters :action="route('admin.projects.index')" placeholder="Search projects">
            <div>
                <label for="filter-status" class="sr-only">Status</label>
                <select id="filter-status" name="status" class="a-input">
                    <option value="">All statuses</option>
                    @foreach (\App\Enums\PublicationStatus::options() as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filter-type" class="sr-only">Type</label>
                <select id="filter-type" name="type" class="a-input">
                    <option value="">All types</option>
                    @foreach (\App\Enums\ProjectType::options() as $value => $label)
                        <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filter-category" class="sr-only">Category</label>
                <select id="filter-category" name="category" class="a-input">
                    <option value="">All categories</option>
                    @foreach ($categories as $id => $name)
                        <option value="{{ $id }}" @selected((string) request('category') === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        </x-admin.filters>

        @if ($projects->isEmpty())
            <div class="p-6">
                <x-admin.empty-state title="No case studies" icon="briefcase">
                    {{ $isFiltered ? 'No projects match these filters.' : 'Add a case study when a project is complete and cleared for disclosure. Outcomes are optional. Only include results you can evidence.' }}
                </x-admin.empty-state>
            </div>
        @else
            @if ($canManage && ! $isFiltered)
                <p class="border-b border-line px-4 py-2 text-xs a-subtle">Drag rows to set the order used on the Work page (featured projects always appear first).</p>
            @endif
            <ul class="divide-y divide-line" @if ($canManage && ! $isFiltered) x-data="sortableList(@js(route('admin.reorder', 'projects')))" @endif>
                @foreach ($projects as $project)
                    <li class="flex items-center gap-4 px-4 py-3" data-id="{{ $project->id }}">
                        @if ($canManage && ! $isFiltered)
                            <span class="a-drag-handle a-icon-btn" aria-hidden="true"><x-icon name="drag" :size="16" /></span>
                        @endif
                        <div class="a-thumb a-checker size-12 flex-none !rounded-lg">
                            @if ($project->cover)
                                <img src="{{ $project->cover->variantUrl(480) }}" alt="">
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            @if ($canManage)
                                <a href="{{ route('admin.projects.edit', $project) }}" class="font-semibold hover:text-lime">{{ $project->title }}</a>
                            @else
                                <span class="font-semibold">{{ $project->title }}</span>
                            @endif
                            <p class="truncate text-xs a-subtle">{{ $project->project_type->label() }} · {{ $project->category?->name ?? 'Uncategorised' }} @if ($project->is_confidential) · Confidential client @endif</p>
                        </div>
                        @if ($project->is_featured)
                            <span class="a-badge a-badge-info">Featured</span>
                        @endif
                        <x-admin.status-badge :status="$project->status" />
                        @if ($canManage)
                            <a href="{{ route('admin.projects.edit', $project) }}" class="a-icon-btn" aria-label="Edit {{ $project->title }}"><x-icon name="edit" :size="16" /></a>
                        @endif
                    </li>
                @endforeach
            </ul>
            {{ $projects->links('admin.partials.pagination') }}
        @endif
    </div>
@endsection
