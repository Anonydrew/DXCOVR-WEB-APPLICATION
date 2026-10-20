@extends('layouts.site')

@php
    $blogName = \App\Models\Page::system('insights')?->title ?? 'Insights';
    $primaryCategory = $article->categories->first();
    $shareUrl = route('insights.show', $article);
    $lead = $recent->first();
    $more = $recent->slice(1);
@endphp

@section('content')
    @include('site.partials.preview-banner', ['status' => $article->status->label()])

    <article class="surface-dark">
        <header class="container-dx pt-10 pb-12 sm:pt-12 sm:pb-16">
            <nav aria-label="Breadcrumb">
                <ol class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm font-medium">
                    <li><a href="{{ route('insights.index') }}" class="transition-opacity hover:opacity-70">{{ $blogName }}</a></li>
                    @if ($primaryCategory)
                        <li aria-hidden="true" class="text-subtle"><x-icon name="chevron-right" :size="14" /></li>
                        <li><a href="{{ route('insights.category', $primaryCategory) }}" class="transition-opacity hover:opacity-70">{{ $primaryCategory->name }}</a></li>
                    @endif
                    <li aria-hidden="true" class="text-subtle"><x-icon name="chevron-right" :size="14" /></li>
                    <li class="accent" aria-current="page">{{ $article->title }}</li>
                </ol>
            </nav>

            <h1 class="blog-title mt-8 max-w-6xl">{{ $article->title }}</h1>

            <div class="mt-10 flex flex-wrap items-center justify-between gap-4">
                @if ($article->categories->isNotEmpty())
                    <ul class="flex flex-wrap gap-2">
                        @foreach ($article->categories as $category)
                            <li><a href="{{ route('insights.category', $category) }}" class="story-pill story-pill-lg">{{ $category->name }}</a></li>
                        @endforeach
                    </ul>
                @else
                    <span></span>
                @endif
                @unless ($isPreview)
                    @include('site.insights.partials.share', ['url' => $shareUrl, 'title' => $article->title])
                @endunless
            </div>

            <p class="mt-5 flex flex-wrap items-center gap-2 text-sm font-semibold text-muted">
                <x-icon name="clock" :size="16" /> {{ $article->reading_time }} min read
                @if ($article->published_at)
                    <span aria-hidden="true">•</span> <time datetime="{{ $article->published_at->toIso8601String() }}">{{ $article->published_at->format('j M Y') }}</time>
                @endif
                @if ($article->show_author)
                    <span aria-hidden="true">•</span> {{ $article->bylineName() }}
                @endif
            </p>
        </header>

        @if ($article->featuredImage)
            <figure class="blog-hero-media">
                <x-site.picture :media="$article->featuredImage" :eager="true" sizes="100vw" class="h-full w-full object-cover" />
                @if ($primaryCategory)
                    <span class="story-chip" aria-hidden="true">{{ $primaryCategory->name }}</span>
                @endif
            </figure>
        @endif

        <div class="container-dx pt-14 pb-20 sm:pt-20 sm:pb-28">
            <div class="blog-body mx-auto">
                @if ($toc)
                    <nav aria-label="On this page" class="mb-12 rounded-2xl border border-line p-6" data-toc>
                        <p class="text-sm font-semibold text-muted">On this page</p>
                        <ul class="mt-3 space-y-1.5 text-sm">
                            @foreach ($toc as $entry)
                                <li class="{{ $entry['level'] === 3 ? 'pl-4' : '' }}"><a href="#{{ $entry['id'] }}" class="toc-link text-muted hover:text-text">{{ $entry['text'] }}</a></li>
                            @endforeach
                        </ul>
                    </nav>
                @endif

                <div class="prose-dx blog-prose">{!! $body !!}</div>

                @if ($article->tags->isNotEmpty())
                    <ul class="mt-14 flex flex-wrap gap-2 border-t border-line pt-8">
                        @foreach ($article->tags as $tag)
                            <li><a href="{{ route('insights.tag', $tag) }}" class="chip">#{{ $tag->name }}</a></li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </article>

    @if ($lead)
        <section class="surface-dark border-t border-line pt-16 pb-24 sm:pt-24 sm:pb-32" aria-labelledby="recent-posts">
            <div class="container-dx">
                <h2 id="recent-posts" class="blog-heading-xl">Recent posts</h2>
                <a href="{{ route('insights.index') }}#recent" class="btn btn-secondary mt-8">View all recent stories <x-icon name="arrow-up-right" :size="18" /></a>

                <div class="mt-12 grid gap-6 lg:grid-cols-12">
                    <x-site.story-card :article="$lead" class="lg:col-span-8" :excerpt-lines="6" />
                    @if ($more->isNotEmpty())
                        <ul class="self-start rounded-[1.25rem] border border-line-strong p-2 lg:col-span-4">
                            @foreach ($more as $item)
                                <li class="story-mini group">
                                    <span class="story-mini-thumb" aria-hidden="true">
                                        @if ($item->featuredImage)
                                            <x-site.picture :media="$item->featuredImage" sizes="64px" alt="" class="h-full w-full object-cover" />
                                        @else
                                            <x-site.cover-art :seed="$item->slug" glyph="ai" />
                                        @endif
                                    </span>
                                    <span class="min-w-0">
                                        <a href="{{ route('insights.show', $item) }}" class="stretched-link line-clamp-2 font-display font-semibold leading-snug transition-opacity group-hover:opacity-80">{{ $item->title }}</a>
                                        @if ($item->published_at)
                                            <time class="mt-1 block text-sm font-semibold text-muted" datetime="{{ $item->published_at->toDateString() }}">{{ $item->published_at->format('j M Y') }}</time>
                                        @endif
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </section>
    @endif
@endsection
