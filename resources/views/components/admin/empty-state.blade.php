@props(['title', 'icon' => 'layers'])
<div {{ $attributes->merge(['class' => 'rounded-2xl border border-dashed border-line-strong px-6 py-14 text-center']) }}>
    <span class="mx-auto grid size-12 place-items-center rounded-full bg-surface-2 text-muted"><x-icon :name="$icon" :size="22" /></span>
    <h2 class="mt-4 text-lg font-semibold">{{ $title }}</h2>
    <div class="mx-auto mt-1.5 max-w-md text-sm a-muted">{{ $slot }}</div>
    @isset($actions)
        <div class="mt-6 flex flex-wrap justify-center gap-2">{{ $actions }}</div>
    @endisset
</div>
