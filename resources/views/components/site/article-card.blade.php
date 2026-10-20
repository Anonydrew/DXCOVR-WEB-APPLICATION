@props(['article', 'featured' => false, 'headingLevel' => 'h3', 'showImage' => true])
<article {{ $attributes->merge(['class' => 'card card-link group flex h-full flex-col']) }}>
    @if ($showImage)
        <div class="media-frame {{ $featured ? 'aspect-[16/9]' : 'aspect-[16/10]' }}">
            @if ($article->featuredImage)
                <x-site.picture :media="$article->featuredImage" sizes="(min-width: 1024px) 33vw, 100vw" class="h-full w-full object-cover" />
            @else
                <x-site.cover-art :seed="$article->slug" glyph="ai" :label="$article->title" />
            @endif
        </div>
    @endif
    <div class="flex flex-1 flex-col gap-3 p-6 sm:p-7">
        <p class="mono-label text-subtle">
            @if ($article->categories->isNotEmpty())
                <span class="accent">{{ $article->categories->first()->name }}</span> ·
            @endif
            <time datetime="{{ $article->published_at?->toDateString() }}">{{ $article->published_at?->format('j M Y') }}</time>
        </p>
        <{{ $headingLevel }} class="{{ $featured ? 'heading-lg' : 'heading-md' }}">
            <a href="{{ route('insights.show', $article) }}" class="stretched-link">{{ $article->title }}</a>
        </{{ $headingLevel }}>
        @if ($article->excerpt)
            <p class="text-muted {{ $featured ? 'line-clamp-4' : 'line-clamp-3' }}">{{ $article->excerpt }}</p>
        @endif
        <p class="mt-auto pt-3 text-sm text-subtle">{{ $article->reading_time }} min read</p>
    </div>
</article>
