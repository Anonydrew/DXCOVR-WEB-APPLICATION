@props(['items' => []])
<nav aria-label="Breadcrumb" {{ $attributes }}>
    <ol class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-subtle">
        @foreach ($items as $item)
            <li class="flex items-center gap-2">
                @if (! $loop->last)
                    <a href="{{ $item['url'] }}" class="transition hover:text-text">{{ $item['name'] }}</a>
                    <x-icon name="chevron-right" :size="14" class="opacity-60" />
                @else
                    <span aria-current="page" class="text-muted">{{ \Illuminate\Support\Str::limit($item['name'], 60) }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
