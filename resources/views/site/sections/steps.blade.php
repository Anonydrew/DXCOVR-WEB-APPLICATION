@php($items = collect($section->value('items', [])))
<section class="surface-raised section" aria-labelledby="steps-{{ $section->id }}">
    <div class="container-dx">
        <x-site.section-heading
            :id="'steps-'.$section->id"
            :eyebrow="$section->value('eyebrow')"
            :heading="$section->value('heading')"
            :intro="$section->value('intro')"
        />
        <ol class="mt-14 grid gap-10 sm:grid-cols-2 {{ $items->count() >= 4 ? 'lg:grid-cols-4' : 'lg:grid-cols-3' }}">
            @foreach ($items as $item)
                <li class="relative border-t border-line-strong pt-7" data-reveal style="--reveal-delay:{{ $loop->index * 0.06 }}s">
                    <span class="absolute -top-px left-0 h-px w-16 bg-lime" aria-hidden="true"></span>
                    <span class="mono-label accent">Step {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                    <h3 class="heading-md mt-4">{{ $item['title'] }}</h3>
                    @if (! empty($item['body']))
                        <p class="mt-3 text-muted">{{ $item['body'] }}</p>
                    @endif
                </li>
            @endforeach
        </ol>
    </div>
</section>
