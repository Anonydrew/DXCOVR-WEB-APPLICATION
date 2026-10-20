@php
    $media = $data['media'] ?? null;
    $imageFirst = $section->value('layout') === 'left';
@endphp
<section class="surface-dark section">
    <div class="container-dx grid items-center gap-12 lg:grid-cols-12">
        <div class="{{ $imageFirst ? 'lg:order-2 lg:col-span-5 lg:col-start-8' : 'lg:col-span-5' }}">
            @if ($section->value('eyebrow'))
                <p class="eyebrow accent" data-reveal>{{ $section->value('eyebrow') }}</p>
            @endif
            @if ($section->value('heading'))
                <h2 class="display-md mt-5" data-reveal>{{ \App\Support\Text::emphasis($section->value('heading')) }}</h2>
            @endif
            @if ($section->value('body'))
                <div class="lede text-muted mt-6 space-y-5" data-reveal>{{ \App\Support\Text::paragraphs($section->value('body')) }}</div>
            @endif
            @if ($section->value('cta_label') && $section->value('cta_url'))
                <a href="{{ $section->value('cta_url') }}" class="btn btn-secondary mt-9">{{ $section->value('cta_label') }} <x-icon name="arrow-right" :size="18" class="btn-arrow" /></a>
            @endif
        </div>
        <div class="{{ $imageFirst ? 'lg:order-1 lg:col-span-6' : 'lg:col-span-6 lg:col-start-7' }}" data-reveal>
            <div class="media-frame aspect-[4/3] rounded-[var(--radius-card)] border border-line">
                @if ($media)
                    <x-site.picture :media="$media" sizes="(min-width: 1024px) 50vw, 100vw" class="h-full w-full object-cover" />
                @else
                    <x-site.cover-art :seed="'section-'.$section->id" glyph="design" />
                @endif
            </div>
        </div>
    </div>
</section>
