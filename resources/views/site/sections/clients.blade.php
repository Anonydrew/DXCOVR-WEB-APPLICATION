@php
    $library = $data['library'] ?? collect();
    $items = collect($section->value('items', []))
        ->filter(fn ($item) => filled($item['name'] ?? null))
        ->map(fn ($item) => $item + ['logo' => ! empty($item['media_id']) ? $library->get((int) $item['media_id']) : null])
        ->values();
    $headingId = 'clients-'.$section->id;
@endphp
<section class="surface-light section" aria-labelledby="{{ $headingId }}">
    <div class="container-dx grid gap-12 lg:grid-cols-12 lg:gap-8">
        <div class="lg:col-span-4">
            <h2 id="{{ $headingId }}" class="clients-heading" data-reveal>{{ $section->value('heading') ?: 'Clients' }}</h2>
            @if ($section->value('intro'))
                <p class="mt-6 max-w-sm text-lg leading-relaxed text-muted" data-reveal style="--reveal-delay:.05s">{{ $section->value('intro') }}</p>
            @endif
        </div>

        <ul class="grid gap-x-10 gap-y-14 sm:grid-cols-2 lg:col-span-8 lg:grid-cols-3 lg:gap-y-20">
            @foreach ($items as $item)
                <li class="client-item group relative" data-reveal style="--reveal-delay:{{ min($loop->index * 0.04, 0.3) }}s">
                    <div class="flex h-14 items-center">
                        @if ($item['logo'])
                            <img src="{{ $item['logo']->variantUrl(480) }}" alt="{{ $item['name'] }}" class="client-logo" loading="lazy" decoding="async">
                        @else
                            <p class="font-display text-2xl font-semibold tracking-tight">{{ $item['name'] }}</p>
                        @endif
                    </div>
                    <p class="mt-6 max-w-xs text-[1.05rem] leading-snug">
                        @if (! empty($item['url']))
                            <a href="{{ $item['url'] }}" class="stretched-link client-link">{{ $item['body'] ?? '' }}</a>
                        @else
                            {{ $item['body'] ?? '' }}
                        @endif
                    </p>
                    @if (! empty($item['url']))
                        <span class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold accent">View the work <x-icon name="arrow-right" :size="16" class="btn-arrow" /></span>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
</section>
