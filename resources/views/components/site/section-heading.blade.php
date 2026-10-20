@props(['eyebrow' => null, 'heading' => null, 'intro' => null, 'id' => null, 'level' => 'h2', 'ctaLabel' => null, 'ctaUrl' => null, 'size' => 'lg'])
@if ($eyebrow || $heading || $intro)
    <div {{ $attributes->merge(['class' => 'grid gap-6 lg:grid-cols-12 lg:items-end']) }}>
        <div class="lg:col-span-8">
            @if ($eyebrow)
                <p class="eyebrow accent" data-reveal>{{ $eyebrow }}</p>
            @endif
            @if ($heading)
                <{{ $level }} @if ($id) id="{{ $id }}" @endif class="{{ $size === 'xl' ? 'display-lg' : 'display-md' }} mt-5" data-reveal style="--reveal-delay:.05s">{{ $heading }}</{{ $level }}>
            @endif
            @if ($intro)
                <p class="lede text-muted mt-5 max-w-2xl" data-reveal style="--reveal-delay:.1s">{{ $intro }}</p>
            @endif
        </div>
        @if ($ctaLabel && $ctaUrl)
            <div class="lg:col-span-4 lg:flex lg:justify-end">
                <a href="{{ $ctaUrl }}" class="link-arrow">
                    {{ $ctaLabel }}
                    <x-icon name="arrow-right" :size="18" class="btn-arrow accent" />
                </a>
            </div>
        @endif
    </div>
@endif
