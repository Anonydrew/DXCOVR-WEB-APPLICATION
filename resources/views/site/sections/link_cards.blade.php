@php
    $library = $data['library'] ?? collect();
    $items = collect($section->value('items', []))->filter(fn ($item) => filled($item['label'] ?? null) && filled($item['url'] ?? null));
    $headingId = 'link-cards-'.$section->id;
    $trackId = 'link-cards-track-'.$section->id;
@endphp
<section class="surface-light about-section" aria-labelledby="{{ $headingId }}" aria-roledescription="carousel" data-carousel>
    <div class="container-dx">
        <h2 id="{{ $headingId }}" class="about-heading" data-reveal>{{ $section->value('heading') ?: 'Get to know us' }}</h2>
    </div>

    <ul id="{{ $trackId }}" class="about-track about-track-bleed mt-12" data-carousel-track tabindex="0" aria-label="{{ $section->value('heading') ?: 'Get to know us' }}. Use the arrow keys or buttons to scroll.">
        @foreach ($items as $item)
            @php $photo = ! empty($item['media_id']) ? $library->get((int) $item['media_id']) : null; @endphp
            <li class="link-card" data-carousel-slide>
                <div class="link-card-media" aria-hidden="true">
                    @if ($photo)
                        <x-site.picture :media="$photo" sizes="(min-width: 768px) 25rem, 80vw" alt="" class="h-full w-full object-cover" />
                    @else
                        <x-site.cover-art :seed="$item['label']" :glyph="$item['glyph'] ?? 'software'" />
                    @endif
                </div>
                <div class="link-card-body">
                    <p class="font-display text-2xl font-semibold text-white">{{ $item['label'] }}</p>
                    <a href="{{ $item['url'] }}" class="btn btn-white mt-4">{{ $item['button_label'] }}</a>
                </div>
            </li>
        @endforeach
    </ul>

    @if ($items->count() > 1)
        <div class="container-dx mt-6 flex justify-end gap-2">
            <button type="button" class="carousel-button" data-carousel-prev aria-controls="{{ $trackId }}" aria-label="Previous cards"><x-icon name="arrow-left" :size="20" /></button>
            <button type="button" class="carousel-button" data-carousel-next aria-controls="{{ $trackId }}" aria-label="Next cards"><x-icon name="arrow-right" :size="20" /></button>
            <p class="sr-only" aria-live="polite" data-carousel-status></p>
        </div>
    @endif
</section>
