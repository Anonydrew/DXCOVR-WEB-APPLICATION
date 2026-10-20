@extends('layouts.site')

@php
    $technologyName = $filters['technology'] ? $technologies->firstWhere('slug', $filters['technology'])?->name : null;
    $without = fn (string $key) => route('work.index', array_filter(array_merge($filters, [$key => null])));
@endphp

@section('content')
    <section class="surface-light work-page" aria-labelledby="work-heading">
        <div class="work-page-inner">
            <header class="work-page-header">
                <p class="work-page-kicker" aria-hidden="true">work</p>
                <h1 id="work-heading" class="work-page-title">{{ $page?->title ?? 'Work that solves real problems' }}</h1>
            </header>

            @if ($categories->isNotEmpty() || $services->isNotEmpty() || $hasFilters)
                <form method="GET" action="{{ route('work.index') }}" class="work-filters" aria-label="Filter projects">
                    <ul class="flex flex-wrap gap-2">
                        <li><a href="{{ $without('category') }}" class="work-pill" @if (! $filters['category']) aria-current="true" @endif>All</a></li>
                        @foreach ($categories as $category)
                            <li><a href="{{ route('work.index', array_filter(array_merge($filters, ['category' => $category->slug]))) }}" class="work-pill" @if ($filters['category'] === $category->slug) aria-current="true" @endif>{{ $category->name }}</a></li>
                        @endforeach
                        @if ($technologyName)
                            <li><a href="{{ $without('technology') }}" class="work-pill" aria-current="true" aria-label="Remove filter: {{ $technologyName }}">{{ $technologyName }} <x-icon name="close" :size="14" /></a></li>
                        @endif
                        @if ($filters['type'])
                            <li><a href="{{ $without('type') }}" class="work-pill" aria-current="true" aria-label="Remove filter: {{ $types[$filters['type']] }}">{{ $types[$filters['type']] }} <x-icon name="close" :size="14" /></a></li>
                        @endif
                    </ul>

                    @foreach (['category', 'technology', 'type'] as $kept)
                        @if ($filters[$kept])
                            <input type="hidden" name="{{ $kept }}" value="{{ $filters[$kept] }}">
                        @endif
                    @endforeach

                    @if ($services->isNotEmpty())
                        <div class="flex max-w-full items-center gap-2">
                            <label for="filter-service" class="sr-only">Service</label>
                            <select id="filter-service" name="service" class="work-select" data-autosubmit>
                                <option value="">All services</option>
                                @foreach ($services as $service)
                                    <option value="{{ $service->slug }}" @selected($filters['service'] === $service->slug)>{{ $service->title }}</option>
                                @endforeach
                            </select>
                            <noscript><button type="submit" class="work-pill">Show</button></noscript>
                        </div>
                    @endif
                </form>
            @endif

            <h2 class="sr-only">Projects</h2>

            @if ($projects->isEmpty())
                <div class="mt-16">
                    @if ($hasFilters)
                        <x-site.empty-state title="No projects match these filters" glyph="modelling" class="border-paper-line">
                            Try removing a filter, or browse everything we have published.
                            <x-slot:actions>
                                <a href="{{ route('work.index') }}" class="btn btn-secondary">Show all work</a>
                            </x-slot:actions>
                        </x-site.empty-state>
                    @else
                        <x-site.empty-state title="Case studies are on their way" glyph="modelling" class="border-paper-line">
                            We publish case studies only when they are complete and cleared for disclosure. Until then, we are happy to walk you through relevant work in a conversation.
                            <x-slot:actions>
                                <a href="{{ route('contact') }}" class="btn btn-primary">Talk to us</a>
                                <a href="{{ route('products.index') }}" class="btn btn-secondary">See our products</a>
                            </x-slot:actions>
                        </x-site.empty-state>
                    @endif
                </div>
            @else
                <ul class="work-grid">
                    @foreach ($projects as $project)
                        <li class="work-card" data-reveal style="--reveal-delay:{{ min(($loop->index % 2) * 0.06, 0.06) }}s">
                            <a href="{{ route('work.show', $project) }}" class="group block">
                                <div class="work-card-media">
                                    @if ($project->cover)
                                        <x-site.picture :media="$project->cover" sizes="(min-width: 768px) 50vw, 100vw" class="h-full w-full object-cover" />
                                    @else
                                        <x-site.cover-art :seed="$project->slug" :glyph="$project->services->first()?->icon ?? 'software'" :label="$project->title" />
                                    @endif
                                    <span class="work-card-badge">{{ $project->project_type->shortLabel() }}</span>
                                </div>
                                <h3 class="work-card-title">{{ $project->title }}</h3>
                                <p class="work-card-line">{{ $project->shortLine() }}</p>
                            </a>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-16">{{ $projects->links() }}</div>
            @endif
        </div>
    </section>
@endsection
