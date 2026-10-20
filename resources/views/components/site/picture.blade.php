@props(['media', 'sizes' => '100vw', 'eager' => false, 'alt' => null])
{{-- Responsive image from the media library with WebP renditions and intrinsic dimensions (no layout shift). --}}
@if ($media)
    <img
        src="{{ $media->variantUrl(960) }}"
        @if ($media->srcset()) srcset="{{ $media->srcset() }}" sizes="{{ $sizes }}" @endif
        alt="{{ $alt ?? ($media->alt ?? '') }}"
        @if ($media->width) width="{{ $media->width }}" height="{{ $media->height }}" @endif
        loading="{{ $eager ? 'eager' : 'lazy' }}"
        @if ($eager) fetchpriority="high" @endif
        decoding="async"
        {{ $attributes }}
    >
@endif
