@php
    $heading = \App\Support\Text::plain($section->value('heading'));
@endphp
<nav class="surface-light pb-16 sm:pb-20" aria-label="{{ $heading ?: 'Quick links' }}">
    <div class="container-dx">
        @if ($heading)
            <h2 class="mono-label mb-8 text-center text-paper-muted">{{ $heading }}</h2>
        @endif
        <ul class="flex flex-wrap justify-center gap-x-6 gap-y-8 sm:gap-x-12">
            @foreach ($section->value('items', []) as $item)
                <li>
                    <a href="{{ $item['url'] }}" class="quick-link">
                        <x-icon :name="$item['icon'] ?? 'grid'" :size="30" class="quick-link-icon" />
                        <span class="quick-link-label">{{ $item['label'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</nav>
