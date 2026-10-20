@extends('layouts.site')

@section('content')
    @include('site.partials.preview-banner', ['status' => $project->status->label()])

    <article>
        <header class="surface-dark relative overflow-hidden">
            <div class="container-dx relative pt-12 sm:pt-16">
                <x-site.breadcrumbs :items="$seo->breadcrumbItems()" />
                <div class="mt-10 grid gap-10 lg:grid-cols-12 lg:items-end">
                    <div class="lg:col-span-8">
                        <div class="flex flex-wrap gap-2">
                            <span class="badge {{ $project->project_type === \App\Enums\ProjectType::Client ? 'badge-lime' : '' }}">{{ $project->project_type->label() }}</span>
                            @if ($project->delivery_status)
                                <span class="badge">{{ $project->delivery_status->label() }}</span>
                            @endif
                        </div>
                        <h1 class="display-lg mt-6">{{ $project->title }}</h1>
                        <p class="lede text-muted mt-6 max-w-3xl">{{ $project->summary }}</p>
                    </div>
                    @if ($project->external_url)
                        <div class="lg:col-span-4 lg:flex lg:justify-end">
                            <a href="{{ $project->external_url }}" class="btn btn-secondary" target="_blank" rel="noopener">Visit project <x-icon name="arrow-up-right" :size="16" /></a>
                        </div>
                    @endif
                </div>

                <dl class="mt-12 grid grid-cols-2 gap-px overflow-hidden rounded-t-[var(--radius-card)] border border-b-0 border-line bg-line text-sm sm:grid-cols-4">
                    <div class="bg-ink p-5">
                        <dt class="mono-label text-subtle">Client</dt>
                        <dd class="mt-2 font-medium">{{ $project->publicClientLabel() ?? ($project->project_type === \App\Enums\ProjectType::Client ? 'Confidential' : 'DXCOVR') }}</dd>
                    </div>
                    <div class="bg-ink p-5">
                        <dt class="mono-label text-subtle">Category</dt>
                        <dd class="mt-2 font-medium">{{ $project->category?->name ?? 'General' }}</dd>
                    </div>
                    <div class="bg-ink p-5">
                        <dt class="mono-label text-subtle">{{ $project->duration ? 'Duration' : 'Year' }}</dt>
                        <dd class="mt-2 font-medium">{{ $project->duration ?? $project->year ?? 'Not stated' }}</dd>
                    </div>
                    <div class="bg-ink p-5">
                        <dt class="mono-label text-subtle">Services</dt>
                        <dd class="mt-2 font-medium">
                            @forelse ($project->services as $service)
                                <a href="{{ route('services.show', $service) }}" class="link-underline">{{ $service->title }}</a>@if (! $loop->last), @endif
                            @empty
                                Not stated
                            @endforelse
                        </dd>
                    </div>
                </dl>
            </div>
        </header>

        <div class="surface-dark">
            <div class="container-dx">
                <div class="media-frame aspect-[16/9] overflow-hidden rounded-b-[var(--radius-card)] border border-t-0 border-line">
                    @if ($project->cover)
                        <x-site.picture :media="$project->cover" :eager="true" sizes="(min-width: 1400px) 1360px, 100vw" class="h-full w-full object-cover" />
                    @else
                        <x-site.cover-art :seed="$project->slug" :glyph="$project->services->first()?->icon ?? 'software'" :label="$project->title" />
                    @endif
                </div>
                @if ($project->project_type === \App\Enums\ProjectType::Concept)
                    <p class="mt-4 text-sm text-subtle">This is a concept design created by DXCOVR to explore an idea. It is not a delivered client project.</p>
                @elseif ($project->project_type === \App\Enums\ProjectType::Prototype)
                    <p class="mt-4 text-sm text-subtle">This is a prototype or experiment developed by DXCOVR.</p>
                @endif
            </div>
        </div>

        @if ($contentSections->isNotEmpty())
            <div class="surface-light section">
                <div class="container-dx space-y-20">
                    @foreach ($contentSections as $key => $block)
                        <section class="grid gap-8 lg:grid-cols-12" aria-labelledby="cs-{{ $key }}">
                            <h2 id="cs-{{ $key }}" class="eyebrow accent lg:col-span-3">{{ $block['label'] }}</h2>
                            <div class="prose-dx lg:col-span-8 lg:col-start-5">{!! $block['html'] !!}</div>
                        </section>
                    @endforeach
                </div>
            </div>
        @endif

        @if (! empty($project->features))
            <section class="surface-dark section" aria-labelledby="features">
                <div class="container-dx">
                    <x-site.section-heading id="features" eyebrow="Features" heading="What we built" />
                    <ul class="mt-12 grid gap-px overflow-hidden rounded-[var(--radius-card)] border border-line bg-line sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($project->features as $feature)
                            <li class="bg-ink p-7">
                                <h3 class="heading-md">{{ $feature['title'] }}</h3>
                                @if (! empty($feature['body']))
                                    <p class="mt-3 text-muted">{{ $feature['body'] }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </section>
        @endif

        @if ($gallery->isNotEmpty() || ! empty($videos))
            <section class="surface-raised section" aria-labelledby="gallery">
                <div class="container-dx">
                    <h2 id="gallery" class="eyebrow accent">Gallery</h2>
                    <div class="mt-10 grid gap-6 md:grid-cols-2">
                        @foreach ($videos as $video)
                            <div class="media-frame aspect-video rounded-[var(--radius-card)] border border-line md:col-span-2">
                                <iframe src="{{ $video['src'] }}" title="{{ $project->title }}, video {{ $loop->iteration }}" class="absolute inset-0 h-full w-full" loading="lazy" allow="accelerometer; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                            </div>
                        @endforeach
                        @foreach ($gallery as $item)
                            <figure class="{{ $loop->index % 3 === 0 ? 'md:col-span-2' : '' }}">
                                <div class="media-frame rounded-[var(--radius-card)] border border-line">
                                    <x-site.picture :media="$item->media" sizes="(min-width: 768px) {{ $loop->index % 3 === 0 ? '100vw' : '50vw' }}, 100vw" class="w-full" />
                                </div>
                                @if ($item->caption || $item->media->caption)
                                    <figcaption class="mt-3 text-sm text-subtle">{{ $item->caption ?: $item->media->caption }}</figcaption>
                                @endif
                            </figure>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @if ($project->technologies->isNotEmpty() || $project->products->isNotEmpty())
            <section class="surface-dark section-tight border-t border-line">
                <div class="container-dx grid gap-10 lg:grid-cols-12">
                    @if ($project->technologies->isNotEmpty())
                        <div class="lg:col-span-7">
                            <h2 class="mono-label text-subtle">Technologies</h2>
                            <ul class="mt-4 flex flex-wrap gap-2">
                                @foreach ($project->technologies as $technology)
                                    <li><a href="{{ route('work.index', ['technology' => $technology->slug]) }}" class="chip">{{ $technology->name }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    @if ($project->products->isNotEmpty())
                        <div class="lg:col-span-5">
                            <h2 class="mono-label text-subtle">Related products</h2>
                            <ul class="mt-4 space-y-2">
                                @foreach ($project->products as $product)
                                    <li><a href="{{ route('products.show', $product) }}" class="link-arrow">{{ $product->name }} <x-icon name="arrow-right" :size="16" class="btn-arrow accent" /></a></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </section>
        @endif
    </article>

    @if ($next)
        <nav class="surface-raised section" aria-label="Next case study">
            <div class="container-dx">
                <p class="mono-label text-subtle">Next case study</p>
                <div class="mt-8 max-w-4xl">
                    <x-site.project-card :project="$next" size="large" heading-level="h2" />
                </div>
            </div>
        </nav>
    @endif
@endsection
