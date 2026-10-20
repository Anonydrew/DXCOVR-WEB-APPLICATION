@props(['title', 'glyph' => 'software'])
<div {{ $attributes->merge(['class' => 'rounded-[var(--radius-card)] border border-dashed border-line-strong px-6 py-14 text-center sm:px-12 sm:py-20']) }}>
    <x-site.glyph :name="$glyph" :size="72" class="mx-auto text-subtle" />
    <h2 class="heading-md mt-6">{{ $title }}</h2>
    <div class="mx-auto mt-3 max-w-xl text-muted">{{ $slot }}</div>
    @isset($actions)
        <div class="mt-8 flex flex-wrap justify-center gap-3">{{ $actions }}</div>
    @endisset
</div>
