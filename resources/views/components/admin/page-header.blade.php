@props(['title', 'description' => null, 'back' => null])
<div {{ $attributes->merge(['class' => 'mb-8 flex flex-wrap items-end justify-between gap-4']) }}>
    <div class="min-w-0">
        @if ($back)
            <a href="{{ $back }}" class="mb-3 inline-flex items-center gap-1.5 text-sm a-muted hover:text-text"><x-icon name="arrow-left" :size="14" /> Back</a>
        @endif
        <h1 class="text-2xl font-semibold sm:text-3xl">{{ $title }}</h1>
        @if ($description)
            <p class="mt-1.5 max-w-3xl a-muted">{{ $description }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
