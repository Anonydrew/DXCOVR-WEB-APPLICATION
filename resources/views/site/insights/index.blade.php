@extends('layouts.site')

@php
    $isIndex = request()->routeIs('insights.index');
    $blogName = $heroPage?->title ?? 'Insights';
    $nothingYet = $isIndex && ! $hero && $articles->isEmpty() && $articles->onFirstPage();
@endphp

@section('content')
    @if ($hero)
        {{-- Latest featured story: full-width image under the transparent header. --}}
        @php
            $heroCategory = $hero->categories->first();
            $heroUrl = route('insights.show', $hero);
        @endphp
        <section class="surface-dark" aria-labelledby="blog-heading">
            <a href="{{ $heroUrl }}" class="blog-hero-media" tabindex="-1" aria-hidden="true">
                @if ($hero->featuredImage)
                    <x-site.picture :media="$hero->featuredImage" :eager="true" sizes="100vw" alt="" class="h-full w-full object-cover" />
                @else
                    <x-site.cover-art :seed="$hero->slug" glyph="ai" />
                @endif
                @if ($heroCategory)
                    <span class="story-chip">{{ $heroCategory->name }}</span>
                @endif
            </a>
            <div class="container-dx pt-10 pb-16 sm:pt-12 sm:pb-24">
                <h1 id="blog-heading" class="blog-label">{{ $blogName }}</h1>
                <h2 class="blog-title mt-5 max-w-5xl"><a href="{{ $heroUrl }}" class="transition-opacity hover:opacity-80">{{ $hero->title }}</a></h2>
                <div class="mt-8 flex flex-wrap items-center justify-between gap-4">
                    <ul class="flex flex-wrap gap-2">
                        @foreach ($hero->categories as $category)
                            <li><a href="{{ route('insights.category', $category) }}" class="story-pill story-pill-lg">{{ $category->name }}</a></li>
                        @endforeach
                    </ul>
                    @include('site.insights.partials.share', ['url' => $heroUrl, 'title' => $hero->title])
                </div>
                <p class="mt-5 flex items-center gap-2 text-sm font-semibold text-muted">
                    <x-icon name="clock" :size="16" /> {{ $hero->reading_time }} min read
                    @if ($hero->published_at)
                        <span aria-hidden="true">•</span> <time datetime="{{ $hero->published_at->toDateString() }}">{{ $hero->published_at->format('j M Y') }}</time>
                    @endif
                </p>
                @if ($hero->excerpt)
                    <p class="mt-5 max-w-4xl text-lg leading-relaxed">{{ $hero->excerpt }}</p>
                @endif
                <a href="{{ $heroUrl }}" class="btn btn-white mt-8">Continue reading</a>
            </div>
        </section>

        @if ($editorsPick->isNotEmpty())
            <section class="surface-dark pb-20 sm:pb-28" aria-labelledby="editors-pick">
                <div class="container-dx">
                    <div class="text-center">
                        <p class="blog-label text-base">Featured</p>
                        <h2 id="editors-pick" class="blog-heading-xl mt-3">Editor’s Pick</h2>
                    </div>
                    <ul class="mt-12 space-y-8 sm:mt-16">
                        @foreach ($editorsPick as $article)
                            <li data-reveal><x-site.story-card :article="$article" :reversed="$loop->odd" /></li>
                        @endforeach
                    </ul>
                </div>
            </section>
        @endif
    @elseif ($nothingYet)
        <x-site.feature-hero key="insights" :page="$heroPage" :title="$heading" :lede="$lede" />
    @endif

    <section id="recent" class="surface-dark {{ $hero || $nothingYet ? 'pt-4' : 'pt-14 sm:pt-20' }} pb-24 sm:pb-32" aria-labelledby="recent-heading">
        <div class="container-dx">
            @if ($nothingYet)
                <x-site.empty-state title="Nothing published here yet" glyph="ai">
                    We write about what we learn while building software, AI systems, connected devices and immersive experiences. The first articles are on the way.
                    @if ($site->get('newsletter.enabled'))
                        <x-slot:actions>
                            <div class="w-full max-w-md text-left"><x-site.newsletter-form id="insights-empty-newsletter" /></div>
                        </x-slot:actions>
                    @endif
                </x-site.empty-state>
            @else
                @if ($isIndex)
                    <p class="blog-label text-base">Collections</p>
                    <h2 id="recent-heading" class="blog-heading-xl mt-3">Recent stories</h2>
                    <p class="mt-6 max-w-2xl text-lg text-muted">Stories, ideas and updates from the {{ $site->get('site.name') }} team.</p>
                @else
                    <a href="{{ route('insights.index') }}" class="blog-label text-base hover:text-text">{{ \App\Models\Page::system('insights')?->title ?? 'Insights' }}</a>
                    <h1 id="recent-heading" class="blog-heading-xl mt-3">{{ $heading }}</h1>
                    @if ($lede)
                        <p class="mt-6 max-w-2xl text-lg text-muted">{{ $lede }}</p>
                    @endif
                @endif

                @if ($categories->isNotEmpty())
                    <form method="GET" action="{{ route('insights.index') }}" class="mt-10 flex max-w-xl gap-3">
                        <label for="collection" class="sr-only">Choose a collection</label>
                        <select id="collection" name="category" class="field blog-select" data-autosubmit>
                            <option value="">All collections</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->slug }}" @selected($activeCategory?->is($category))>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <noscript><button type="submit" class="btn btn-secondary">Show</button></noscript>
                    </form>
                @endif

                @if ($articles->isEmpty())
                    <p class="mt-12 text-muted">No stories here yet. <a href="{{ route('insights.index') }}" class="link-underline text-text">See all stories</a>.</p>
                @else
                    <ul class="mt-12 space-y-8">
                        @foreach ($articles as $article)
                            <li data-reveal><x-site.story-card :article="$article" :reversed="$loop->odd" :heading-level="$isIndex ? 'h3' : 'h2'" /></li>
                        @endforeach
                    </ul>
                    <div class="mt-12">{{ $articles->links() }}</div>
                @endif
            @endif
        </div>
    </section>

    @if ($site->get('newsletter.enabled') && ! $nothingYet)
        <section class="surface-dark section-tight border-t border-line">
            <div class="container-dx grid items-center gap-8 lg:grid-cols-12">
                <div class="lg:col-span-6">
                    <h2 class="heading-lg">{{ $site->get('newsletter.heading') }}</h2>
                    <p class="mt-3 text-muted">{{ $site->get('newsletter.body') }}</p>
                </div>
                <div class="lg:col-span-6"><x-site.newsletter-form id="insights-newsletter" /></div>
            </div>
        </section>
    @endif
@endsection
