@props(['status', 'at' => null])
@php
    $tone = method_exists($status, 'tone') ? $status->tone() : 'neutral';
    $class = match ($tone) {
        'success' => 'a-badge-success',
        'warning' => 'a-badge-warning',
        'info' => 'a-badge-info',
        'danger' => 'a-badge-danger',
        'muted' => 'a-badge-muted',
        default => '',
    };
@endphp
<span {{ $attributes->merge(['class' => 'a-badge '.$class]) }} @if ($at) title="{{ $at->format('j M Y, H:i') }}" @endif>{{ $status->label() }}</span>
