@extends('layouts.site')

@section('content')
    <x-site.page-hero
        eyebrow="Work"
        :title="$page?->title ?? 'Selected work'"
        :lede="$page?->excerpt"
        :breadcrumbs="$seo->breadcrumbItems()"
        glyph="modelling"
    />

    <section class="surface-dark pb-24 sm:pb-32" aria-labelledby="work-results">
        <div class="container-dx">
            <h2 id="work-results" class="sr-only">Projects</h2>

            @if ($categories->isNotEmpty() || $services->isNotEmpty() || $technologies->isNotEmpty())
                <form method="GET" action="{{ route('work.index') }}" class="mb-12 rounded-[var(--radius-card)] border border-line bg-ink-2 p-5 sm:p-6" aria-label="Filter projects">
                    @if ($categories->isNotEmpty())
                        <div class="flex flex-wrap items-center gap-2" role="group" aria-label="Category">
                            <a href="{{ route('work.index', array_filter(array_merge($filters, ['category' => null]))) }}" class="chip" @if (! $filters['category']) aria-current="true" @endif>All categories</a>
                            @foreach ($categories as $category)
                                <a href="{{ route('work.index', array_filter(array_merge($filters, ['category' => $category->slug]))) }}" class="chip" @if ($filters['category'] === $category->slug) aria-current="true" @endif>{{ $category->name }}</a>
                            @endforeach
                        </div>
                    @endif
                    @if ($filters['category'])
                        <input type="hidden" name="category" value="{{ $filters['category'] }}">
                    @endif
                    <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label for="filter-service" class="field-label">Service</label>
                            <select id="filter-service" name="service" class="field" data-autosubmit>
                                <option value="">All services</option>
                                @foreach ($services as $service)
                                    <option value="{{ $service->slug }}" @selected($filters['service'] === $service->slug)>{{ $service->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="filter-technology" class="field-label">Technology</label>
                            <select id="filter-technology" name="technology" class="field" data-autosubmit>
                                <option value="">All technologies</option>
                                @foreach ($technologies as $technology)
                                    <option value="{{ $technology->slug }}" @selected($filters['technology'] === $technology->slug)>{{ $technology->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="filter-type" class="field-label">Project type</label>
                            <select id="filter-type" name="type" class="field" data-autosubmit>
                                <option value="">All types</option>
                                @foreach ($types as $value => $label)
                                    <option value="{{ $value }}" @selected($filters['type'] === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-end gap-3">
                            <button type="submit" class="btn btn-secondary flex-1">Apply filters</button>
                            @if ($hasFilters)
                                <a href="{{ route('work.index') }}" class="btn btn-sm text-muted hover:text-text">Clear</a>
                            @endif
                        </div>
                    </div>
                </form>
            @endif

            @if ($projects->isEmpty())
                @if ($hasFilters)
                    <x-site.empty-state title="No projects match these filters" glyph="modelling">
                        Try removing a filter, or browse everything we have published.
                        <x-slot:actions>
                            <a href="{{ route('work.index') }}" class="btn btn-secondary">Show all work</a>
                        </x-slot:actions>
                    </x-site.empty-state>
                @else
                    <x-site.empty-state title="Case studies are on their way" glyph="modelling">
                        We publish case studies only when they are complete and cleared for disclosure. While we prepare them, we are happy to walk you through relevant work in a conversation.
                        <x-slot:actions>
                            <a href="{{ route('contact') }}" class="btn btn-primary">Talk to us</a>
                            <a href="{{ route('products.index') }}" class="btn btn-secondary">See our products</a>
                        </x-slot:actions>
                    </x-site.empty-state>
                @endif
            @else
                <p class="mb-6 text-sm text-subtle" role="status">{{ trans_choice(':count project|:count projects', $projects->total()) }}</p>
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-12">
                    @foreach ($projects as $project)
                        @php($wide = $projects->onFirstPage() && ! $hasFilters && $loop->first)
                        <div class="{{ $wide ? 'md:col-span-2 lg:col-span-12' : 'lg:col-span-6' }}" data-reveal style="--reveal-delay:{{ min($loop->index * 0.04, 0.24) }}s">
                            <x-site.project-card :project="$project" :size="$wide ? 'large' : 'default'" heading-level="h3" />
                        </div>
                    @endforeach
                </div>
                <div class="mt-12">{{ $projects->links() }}</div>
            @endif
        </div>
    </section>
@endsection
