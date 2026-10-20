@php
    $items = collect($section->value('items', []))->values();
    $library = $data['library'] ?? collect();
    $trackId = 'spotlight-'.$section->id;
    $label = \App\Support\Text::plain($section->value('heading')) ?: 'Featured';
@endphp
<section class="spotlight surface-light relative overflow-hidden py-14 sm:py-20" aria-roledescription="carousel" aria-label="{{ $label }}" data-carousel>
    @if ($section->value('eyebrow') || $section->value('heading'))
        <div class="container-dx mb-10 sm:mb-14">
            <x-site.section-heading :eyebrow="$section->value('eyebrow')" :heading="$section->value('heading') ? \App\Support\Text::plain($section->value('heading')) : null" />
        </div>
    @endif

    <ul id="{{ $trackId }}" class="spotlight-track" data-carousel-track tabindex="0" aria-label="{{ $label }}. Use the arrow keys or buttons to scroll.">
        @foreach ($items as $item)
            @php
                $media = ! empty($item['media_id']) ? $library->get((int) $item['media_id']) : null;
                $tone = $media ? 'photo' : ($item['tone'] ?? 'dark');
            @endphp
            <li class="spotlight-card spotlight-{{ $tone }}" role="group" aria-roledescription="slide" aria-label="{{ $loop->iteration }} of {{ $items->count() }}: {{ $item['title'] }}" data-carousel-slide>
                <div class="spotlight-media" aria-hidden="{{ $media ? 'false' : 'true' }}">
                    @if ($media)
                        <x-site.picture :media="$media" sizes="(min-width: 1024px) 24rem, 80vw" class="h-full w-full object-cover" />
                    @else
                        <div class="spotlight-art grid h-full place-items-center">
                            <x-site.glyph :name="$item['icon'] ?? 'software'" :size="200" />
                        </div>
                    @endif
                </div>

                <div class="spotlight-body">
                    @if (! empty($item['badge']))
                        <span class="spotlight-badge">{{ $item['badge'] }}</span>
                    @endif

                    @if (! empty($item['body']))
                        <details class="spotlight-details">
                            <summary class="flex items-end justify-between gap-4">
                                <h3 class="spotlight-title">{{ $item['title'] }}</h3>
                                <span class="spotlight-toggle" aria-hidden="true"><x-icon name="chevron-down" :size="22" /></span>
                            </summary>
                            <p class="spotlight-text">{{ $item['body'] }}</p>
                        </details>
                    @else
                        <h3 class="spotlight-title">{{ $item['title'] }}</h3>
                    @endif

                    @if (! empty($item['cta_label']) && ! empty($item['cta_url']))
                        <a href="{{ $item['cta_url'] }}" class="spotlight-cta">
                            {{ $item['cta_label'] }}
                            <span class="sr-only">: {{ $item['title'] }}</span>
                        </a>
                    @endif
                </div>
            </li>
        @endforeach
    </ul>

    @if ($items->count() > 1)
        <div class="container-dx mt-8 flex items-center gap-3">
            <button type="button" class="carousel-button" data-carousel-prev aria-controls="{{ $trackId }}" aria-label="Previous cards">
                <x-icon name="arrow-left" :size="20" />
            </button>
            <button type="button" class="carousel-button" data-carousel-next aria-controls="{{ $trackId }}" aria-label="Next cards">
                <x-icon name="arrow-right" :size="20" />
            </button>
            <p class="sr-only" aria-live="polite" data-carousel-status></p>
        </div>
    @endif
</section>
