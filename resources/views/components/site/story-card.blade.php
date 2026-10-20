@props(['article', 'reversed' => false, 'headingLevel' => 'h3', 'excerptLines' => 4])
{{-- Wide blog card: image on one side, category, date, title and excerpt on the other. --}}
@php($category = $article->categories->first())
<article {{ $attributes->merge(['class' => 'story-card group'.($reversed ? ' is-reversed' : '')]) }}>
    <div class="story-card-media">
        @if ($article->featuredImage)
            <x-site.picture :media="$article->featuredImage" sizes="(min-width: 768px) 50vw, 100vw" class="h-full w-full object-cover" />
        @else
            <x-site.cover-art :seed="$article->slug" glyph="ai" :label="$article->title" />
        @endif
    </div>
    <div class="story-card-body">
        <div class="flex items-center justify-between gap-4">
            @if ($category)
                <span class="story-pill">{{ $category->name }}</span>
            @else
                <span></span>
            @endif
            @if ($article->published_at)
                <time class="story-date" datetime="{{ $article->published_at->toDateString() }}">{{ $article->published_at->format('j M Y') }}</time>
            @endif
        </div>
        <{{ $headingLevel }} class="story-card-title">
            <a href="{{ route('insights.show', $article) }}" class="stretched-link">{{ $article->title }}</a>
        </{{ $headingLevel }}>
        @if ($article->excerpt)
            <p class="story-card-excerpt" style="-webkit-line-clamp: {{ $excerptLines }}; line-clamp: {{ $excerptLines }}">{{ $article->excerpt }}</p>
        @endif
    </div>
</article>
