@props(['eyebrow' => null, 'title', 'lede' => null, 'breadcrumbs' => [], 'glyph' => null, 'surface' => 'dark'])
<section {{ $attributes->merge(['class' => ($surface === 'light' ? 'surface-light' : 'surface-dark').' relative overflow-hidden']) }}>
    @if ($surface !== 'light')
        <div class="grid-lines pointer-events-none absolute inset-0 [mask-image:linear-gradient(to_bottom,black,transparent)]" aria-hidden="true"></div>
        <div class="glow-lime pointer-events-none absolute -right-40 -top-40 size-[34rem] opacity-60" aria-hidden="true"></div>
    @endif
    <div class="container-dx relative pt-12 pb-16 sm:pt-16 sm:pb-24">
        @if ($breadcrumbs)
            <x-site.breadcrumbs :items="$breadcrumbs" />
        @endif
        <div class="mt-10 grid items-end gap-10 lg:grid-cols-12">
            <div class="lg:col-span-9">
                @if ($eyebrow)
                    <p class="eyebrow accent" data-reveal>{{ $eyebrow }}</p>
                @endif
                <h1 class="display-lg mt-5 max-w-5xl" data-reveal style="--reveal-delay:.05s">{{ $title }}</h1>
                @if ($lede)
                    <p class="lede text-muted mt-6 max-w-3xl" data-reveal style="--reveal-delay:.1s">{{ $lede }}</p>
                @endif
                {{ $slot }}
            </div>
            @if ($glyph)
                <div class="hidden justify-end text-muted lg:col-span-3 lg:flex" aria-hidden="true">
                    <x-site.glyph :name="$glyph" :size="160" />
                </div>
            @endif
        </div>
    </div>
</section>
