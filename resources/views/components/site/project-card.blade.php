@props(['project', 'size' => 'default', 'headingLevel' => 'h3'])
@php
    $glyph = $project->services->first()?->icon ?? 'software';
    // Large cards grow their image to fill tall grid cells instead of leaving empty space.
    $ratio = $size === 'large' ? 'aspect-[16/10] lg:aspect-auto lg:min-h-[22rem] lg:flex-1' : 'aspect-[4/3]';
@endphp
<article {{ $attributes->merge(['class' => 'card card-link group flex h-full flex-col']) }}>
    <div class="media-frame {{ $ratio }}">
        @if ($project->cover)
            <x-site.picture :media="$project->cover" sizes="(min-width: 1024px) 50vw, 100vw" class="h-full w-full object-cover" />
        @else
            <x-site.cover-art :seed="$project->slug" :glyph="$glyph" :label="$project->title" />
        @endif
        <div class="absolute left-4 top-4 flex flex-wrap gap-2">
            <span class="badge {{ $project->project_type === \App\Enums\ProjectType::Client ? 'badge-lime' : '' }} bg-ink/80 backdrop-blur">{{ $project->project_type->shortLabel() }}</span>
        </div>
    </div>
    <div class="flex {{ $size === 'large' ? '' : 'flex-1' }} flex-col gap-3 p-6 sm:p-7">
        <p class="mono-label text-subtle">
            {{ $project->category?->name ?? 'Case study' }}@if ($project->year) · {{ $project->year }}@endif
        </p>
        <{{ $headingLevel }} class="{{ $size === 'large' ? 'heading-lg' : 'heading-md' }}">
            <a href="{{ route('work.show', $project) }}" class="stretched-link">{{ $project->title }}</a>
        </{{ $headingLevel }}>
        <p class="text-muted line-clamp-3">{{ $project->summary }}</p>
        <span class="mt-auto inline-flex items-center gap-2 pt-3 text-sm font-semibold" aria-hidden="true">
            View case study <x-icon name="arrow-right" :size="16" class="btn-arrow accent" />
        </span>
    </div>
</article>
