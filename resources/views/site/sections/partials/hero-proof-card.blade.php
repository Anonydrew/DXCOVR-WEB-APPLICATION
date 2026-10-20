{{-- One proof card. Expects $item and $library (media keyed by ID). --}}
@php
    $logo = ! empty($item['media_id']) ? $library->get((int) $item['media_id']) : null;
@endphp
<li class="hero-proof-card group">
    <div class="hero-proof-value">
        <p class="hero-proof-number">{{ $item['value'] }}</p>
        @if (! empty($item['label']))
            <p class="hero-proof-caption">{{ $item['label'] }}</p>
        @endif
    </div>
    <div class="hero-proof-content">
        @if ($logo)
            <img src="{{ $logo->variantUrl(480) }}" alt="{{ $item['title'] ?? $logo->alt ?? '' }}" class="hero-proof-logo" loading="lazy" decoding="async">
        @elseif (! empty($item['title']))
            <p class="hero-proof-title">{{ $item['title'] }}</p>
        @endif
        @if (! empty($item['body']))
            <p class="hero-proof-body">{{ $item['body'] }}</p>
        @endif
    </div>
    @if (! empty($item['url']))
        <a href="{{ $item['url'] }}" class="hero-proof-link stretched-link" aria-label="Learn more: {{ $item['title'] ?? ($item['value'].(! empty($item['label']) ? ' '.$item['label'] : '')) }}">
            <x-icon name="arrow-right" :size="20" class="btn-arrow" />
        </a>
    @endif
</li>
