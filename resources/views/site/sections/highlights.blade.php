@php
    $items = collect($section->value('items', []));
@endphp
<section class="surface-dark section" aria-labelledby="highlights-{{ $section->id }}">
    <div class="container-dx">
        <x-site.section-heading
            :id="'highlights-'.$section->id"
            :eyebrow="$section->value('eyebrow')"
            :heading="$section->value('heading')"
            :intro="$section->value('intro')"
        />

        <div class="mt-14 grid gap-5 md:grid-cols-2 lg:grid-cols-12">
            @foreach ($items as $item)
                @php
                    $layout = match ($loop->index % 4) {
                        0 => 'lg:col-span-7 lg:row-span-2 min-h-[24rem] lg:min-h-[34rem]',
                        1, 2 => 'lg:col-span-5 min-h-[16rem]',
                        default => 'lg:col-span-12 min-h-[14rem]',
                    };
                    $isHero = $loop->index % 4 === 0;
                @endphp
                <article class="card {{ ! empty($item['url']) ? 'card-link' : '' }} group relative flex flex-col justify-end overflow-hidden p-7 sm:p-9 {{ $layout }}" data-reveal style="--reveal-delay:{{ $loop->index * 0.06 }}s">
                    <div class="pointer-events-none absolute right-6 top-6 text-line-strong transition duration-700 group-hover:text-muted sm:right-9 sm:top-9" aria-hidden="true">
                        <x-site.glyph :name="$item['icon'] ?? 'software'" :size="$isHero ? 220 : 88" />
                    </div>
                    <div class="relative max-w-xl {{ $isHero ? '' : 'pt-20 sm:pt-16 lg:pr-24' }}">
                        @if (! empty($item['label']))
                            <p class="mono-label accent">{{ $item['label'] }}</p>
                        @endif
                        <h3 class="{{ $isHero ? 'display-md' : 'heading-lg' }} mt-4">
                            @if (! empty($item['url']))
                                <a href="{{ $item['url'] }}" class="stretched-link">{{ $item['title'] }}</a>
                            @else
                                {{ $item['title'] }}
                            @endif
                        </h3>
                        @if (! empty($item['body']))
                            <p class="mt-4 text-muted">{{ $item['body'] }}</p>
                        @endif
                        @if (! empty($item['url']))
                            <span class="mt-6 inline-flex items-center gap-2 text-sm font-semibold" aria-hidden="true">
                                {{ $item['link_label'] ?? 'Learn more' }} <x-icon name="arrow-right" :size="16" class="btn-arrow accent" />
                            </span>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
