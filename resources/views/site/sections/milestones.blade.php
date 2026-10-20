@php
    $library = $data['library'] ?? collect();
    $items = collect($section->value('items', []));
    $headingId = 'milestones-'.$section->id;
    $trackId = 'milestones-track-'.$section->id;
@endphp
<section class="surface-light about-section" aria-labelledby="{{ $headingId }}" aria-roledescription="carousel" data-carousel>
    <div class="container-dx">
        @if ($section->value('eyebrow'))
            <p class="text-sm font-semibold text-paper-muted" data-reveal>{{ $section->value('eyebrow') }}</p>
        @endif
        <h2 id="{{ $headingId }}" class="about-heading mt-3" data-reveal>{{ $section->value('heading') ?: 'History' }}</h2>
        @if ($section->value('intro'))
            <p class="mt-6 max-w-4xl text-lg leading-relaxed text-paper-muted" data-reveal>{{ $section->value('intro') }}</p>
        @endif
    </div>

    <ol id="{{ $trackId }}" class="about-track about-track-bleed mt-12" data-carousel-track tabindex="0" aria-label="Milestones. Use the arrow keys or buttons to scroll.">
        @foreach ($items as $item)
            @php $photo = ! empty($item['media_id']) ? $library->get((int) $item['media_id']) : null; @endphp
            <li class="timeline-card" data-carousel-slide>
                <p class="font-display text-2xl font-semibold">{{ $item['period'] }}</p>
                <p class="mt-4 text-lg font-semibold leading-snug">{{ $item['title'] }}</p>
                @if (! empty($item['body']))
                    <p class="mt-3 leading-relaxed text-muted">{{ $item['body'] }}</p>
                @endif
                @if ($photo)
                    <div class="timeline-card-media">
                        <x-site.picture :media="$photo" sizes="24rem" alt="" class="h-full w-full object-cover" />
                    </div>
                @endif
            </li>
        @endforeach
    </ol>

    @if ($items->count() > 1)
        <div class="container-dx mt-6 flex justify-end gap-2">
            <button type="button" class="carousel-button" data-carousel-prev aria-controls="{{ $trackId }}" aria-label="Earlier milestones"><x-icon name="arrow-left" :size="20" /></button>
            <button type="button" class="carousel-button" data-carousel-next aria-controls="{{ $trackId }}" aria-label="Later milestones"><x-icon name="arrow-right" :size="20" /></button>
            <p class="sr-only" aria-live="polite" data-carousel-status></p>
        </div>
    @endif
</section>
