@props(['page' => null, 'key', 'title', 'lede' => null, 'defaultCtaUrl' => null])
{{-- Full-bleed photo hero for top-level listing pages (Services, Insights). Sits under the transparent header. --}}
@php
    $defaults = \App\Models\Page::FEATURE_HEROES[$key];
    $media = $page?->heroImage;
    $eyebrow = $page?->hero_eyebrow ?: $defaults['eyebrow'];
    $ctaUrl = $page?->hero_cta_url ?: $defaultCtaUrl;
    $ctaLabel = $page?->hero_cta_label ?: $defaults['cta_label'];
    $headingId = 'feature-hero-'.$key;
@endphp
<section {{ $attributes->merge(['class' => 'feature-hero surface-dark relative overflow-hidden']) }} aria-labelledby="{{ $headingId }}">
    <div class="feature-hero-backdrop" aria-hidden="true">
        @if ($media)
            <x-site.picture :media="$media" :eager="true" sizes="100vw" alt="" class="feature-hero-photo" />
        @else
            <div class="feature-hero-artwork">
                <div class="grid-lines absolute inset-0 opacity-70"></div>
                {{-- An original wireframe ribbon rising to the right. --}}
                <svg class="feature-hero-lattice" viewBox="0 0 600 400" fill="none" preserveAspectRatio="xMaxYMax meet">
                    <path d="M40 400 C 200 300, 380 230, 600 205" stroke="currentColor" stroke-width="2.5" />
                    <path d="M120 400 C 260 320, 420 280, 600 268" stroke="currentColor" stroke-width="2.5" />
                    <path d="M200 400 C 320 345, 460 325, 600 322" stroke="currentColor" stroke-width="2.5" opacity=".7" />
                    @foreach ([[96, 366, 158, 400], [170, 322, 226, 368], [250, 284, 300, 336], [338, 254, 380, 310], [430, 230, 466, 291], [520, 214, 548, 279], [596, 205, 600, 268]] as [$x1, $y1, $x2, $y2])
                        <path d="M{{ $x1 }} {{ $y1 }} L{{ $x2 }} {{ $y2 }}" stroke="currentColor" stroke-width="2" opacity=".75" />
                    @endforeach
                </svg>
            </div>
        @endif
        <div class="feature-hero-scrim"></div>
    </div>

    <div class="container-dx relative flex min-h-[inherit] flex-col justify-end pt-32 pb-14 sm:pb-20">
        <div class="grid gap-8 lg:grid-cols-12 lg:items-end">
            <div class="lg:col-span-8 xl:col-span-7 xl:col-start-2">
                <p class="feature-hero-eyebrow" data-reveal><x-icon :name="$defaults['icon']" :size="18" /> {{ $eyebrow }}</p>
                <h1 id="{{ $headingId }}" class="feature-hero-title mt-4" data-reveal style="--reveal-delay:.05s">{{ $title }}</h1>
                @if ($lede)
                    <p class="feature-hero-lede mt-5" data-reveal style="--reveal-delay:.1s">{{ $lede }}</p>
                @endif
            </div>
            @if ($ctaUrl)
                <div class="lg:col-span-4 lg:flex lg:justify-end xl:col-span-3" data-reveal style="--reveal-delay:.15s">
                    <a href="{{ $ctaUrl }}" class="btn btn-primary hero-btn w-full justify-center sm:w-auto">
                        {{ $ctaLabel }}
                        <x-icon name="arrow-right" :size="18" class="btn-arrow" />
                    </a>
                </div>
            @endif
        </div>
    </div>
</section>
