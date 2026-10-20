<nav class="-mt-4 mb-6 flex flex-wrap gap-1 border-b border-line" aria-label="Section">
    @foreach ($tabs as $tab)
        @php
            $params = $tab['params'] ?? [];
            $active = $params === []
                ? request()->routeIs(\Illuminate\Support\Str::beforeLast($tab['route'], '.').'.*')
                : request()->routeIs($tab['route']) && collect($params)->every(fn ($value, $key) => (string) request()->route($key) === (string) $value);
        @endphp
        <a href="{{ route($tab['route'], $params) }}" class="-mb-px border-b-2 px-3 py-2.5 text-sm font-medium {{ $active ? 'border-lime text-text' : 'border-transparent a-muted hover:text-text' }}" @if ($active) aria-current="page" @endif>{{ $tab['label'] }}</a>
    @endforeach
</nav>
