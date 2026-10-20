@php($items = collect($section->value('items', [])))
<section class="surface-dark section" aria-labelledby="features-{{ $section->id }}">
    <div class="container-dx">
        <x-site.section-heading
            :id="'features-'.$section->id"
            :eyebrow="$section->value('eyebrow')"
            :heading="$section->value('heading')"
            :intro="$section->value('intro')"
        />
        <ul class="mt-14 grid gap-px overflow-hidden rounded-[var(--radius-card)] border border-line bg-line sm:grid-cols-2 {{ $items->count() % 3 === 0 ? 'lg:grid-cols-3' : 'lg:grid-cols-2' }}">
            @foreach ($items as $item)
                <li class="bg-ink p-7 sm:p-9" data-reveal style="--reveal-delay:{{ min($loop->index * 0.05, 0.3) }}s">
                    @if (! empty($item['icon']))
                        <x-site.glyph :name="$item['icon']" :size="44" class="text-muted" />
                    @else
                        <span class="mono-label accent">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                    @endif
                    <h3 class="heading-md mt-6">{{ $item['title'] }}</h3>
                    @if (! empty($item['body']))
                        <p class="mt-3 text-muted">{{ $item['body'] }}</p>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
</section>
