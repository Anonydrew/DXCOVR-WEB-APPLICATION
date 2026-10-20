@php
    $services = $data['services'];
    $headingId = 'showcase-'.$section->id;
    $trackId = 'showcase-track-'.$section->id;
    $ctaLabel = $section->value('cta_label') ?: 'Explore this service';
@endphp
<section class="surface-light about-section" aria-labelledby="{{ $headingId }}" aria-roledescription="carousel" data-carousel>
    <div class="container-dx">
        <h2 id="{{ $headingId }}" class="about-heading" data-reveal>{{ $section->value('heading') ?: 'What we do' }}</h2>
        @if ($section->value('intro'))
            <p class="mt-6 max-w-3xl text-lg leading-relaxed text-paper-muted" data-reveal>{{ $section->value('intro') }}</p>
        @endif

        <ul id="{{ $trackId }}" class="about-track mt-12" data-carousel-track tabindex="0" aria-label="Services. Use the arrow keys or buttons to move between them.">
            @foreach ($services as $service)
                <li class="about-slide" role="group" aria-roledescription="slide" aria-label="{{ $loop->iteration }} of {{ $services->count() }}: {{ $service->title }}" data-carousel-slide>
                    <div class="grid items-center gap-8 md:grid-cols-12 md:gap-12">
                        <div class="about-slide-media md:col-span-5">
                            @if ($service->heroImage)
                                <x-site.picture :media="$service->heroImage" sizes="(min-width: 768px) 40vw, 100vw" class="h-full w-full object-cover" />
                            @else
                                <x-site.cover-art :seed="$service->slug" :glyph="$service->icon" :label="$service->title" />
                            @endif
                        </div>
                        <div class="md:col-span-6 md:col-start-7">
                            <h3 class="font-display text-3xl font-semibold tracking-tight">{{ $service->title }}</h3>
                            <p class="mt-4 text-lg leading-relaxed text-paper-muted">{{ $service->short_description }}</p>
                            <a href="{{ route('services.show', $service) }}" class="btn btn-primary mt-8">
                                {{ $ctaLabel }}<span class="sr-only">: {{ $service->title }}</span>
                                <x-icon name="arrow-right" :size="18" class="btn-arrow" />
                            </a>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>

        @if ($services->count() > 1)
            <div class="mt-5 flex items-center justify-between gap-4 md:w-[calc(41.666%-1.5rem)]">
                <button type="button" class="carousel-button" data-carousel-prev aria-controls="{{ $trackId }}" aria-label="Previous service"><x-icon name="arrow-left" :size="20" /></button>
                <div class="flex items-center gap-2">
                    @foreach ($services as $service)
                        <button type="button" class="carousel-dot" data-carousel-dot aria-controls="{{ $trackId }}" aria-label="Show {{ $service->title }}" aria-current="{{ $loop->first ? 'true' : 'false' }}"></button>
                    @endforeach
                </div>
                <button type="button" class="carousel-button" data-carousel-next aria-controls="{{ $trackId }}" aria-label="Next service"><x-icon name="arrow-right" :size="20" /></button>
            </div>
            <p class="sr-only" aria-live="polite" data-carousel-status></p>
        @endif
    </div>
</section>
