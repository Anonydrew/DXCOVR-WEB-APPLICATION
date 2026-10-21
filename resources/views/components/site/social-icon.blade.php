@props(['profile', 'size' => 18])
{{-- A social profile's mark: the uploaded icon, else the network's brand logo, else a link icon. Decorative. --}}
@php($path = \App\Support\SocialNetworks::path($profile['network'] ?? null))
@if (($profile['media'] ?? null) instanceof \App\Models\Media)
    <img src="{{ $profile['media']->variantUrl(480) }}" alt="" width="{{ $size }}" height="{{ $size }}" {{ $attributes->merge(['class' => 'social-custom-icon']) }} loading="lazy" decoding="async">
@elseif ($path)
    <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false" {{ $attributes }}><path d="{{ $path }}" /></svg>
@else
    <x-icon name="link" :size="$size" />
@endif
