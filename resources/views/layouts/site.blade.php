@php
    /** @var \App\Support\Seo\Seo $seo */
    $seo ??= \App\Support\Seo\Seo::make();
    $bodyClass = trim('min-h-dvh flex flex-col '.($bodyClass ?? ''));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $seo->fullTitle() }}</title>
    <meta name="description" content="{{ $seo->metaDescription() }}">
    <meta name="robots" content="{{ $seo->robots() }}">
    <link rel="canonical" href="{{ $seo->canonicalUrl() }}">

    <meta property="og:site_name" content="{{ $seo->siteName() }}">
    <meta property="og:type" content="{{ $seo->ogType() }}">
    <meta property="og:title" content="{{ $seo->ogTitle() }}">
    <meta property="og:description" content="{{ $seo->ogDescription() }}">
    <meta property="og:url" content="{{ $seo->canonicalUrl() }}">
    <meta property="og:locale" content="en_GB">
    @if ($seo->imageUrl())
        <meta property="og:image" content="{{ $seo->imageUrl() }}">
        @if ($seo->imageMedia()?->alt)
            <meta property="og:image:alt" content="{{ $seo->imageMedia()->alt }}">
        @endif
        <meta name="twitter:card" content="summary_large_image">
    @else
        <meta name="twitter:card" content="summary">
    @endif
    @if ($seo->publishedTime())
        <meta property="article:published_time" content="{{ $seo->publishedTime() }}">
    @endif
    @if ($seo->modifiedTime())
        <meta property="article:modified_time" content="{{ $seo->modifiedTime() }}">
    @endif

    <meta name="theme-color" content="#080A08">
    <meta name="csp-nonce" content="{{ Vite::cspNonce() }}">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="32x32">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <link rel="alternate" type="application/rss+xml" title="{{ $seo->siteName() }} Insights" href="{{ route('insights.feed') }}">

    <script nonce="{{ Vite::cspNonce() }}">document.documentElement.classList.add('js');</script>
    @vite(['resources/css/site.css', 'resources/js/site.js'])

    @foreach ($seo->jsonLd() as $schema)
        <script type="application/ld+json" nonce="{{ Vite::cspNonce() }}">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    @endforeach
    @stack('head')
</head>
<body class="{{ $bodyClass }}">
    <a href="#main" class="sr-only-focusable left-4 top-4 z-[100] rounded-full bg-lime px-5 py-3 font-semibold text-ink">Skip to main content</a>

    @if ($announcement)
        <x-site.announcement :announcement="$announcement" />
    @endif

    <x-site.header :items="$primaryNav" :site="$site" />

    <main id="main" tabindex="-1" class="flex-1 focus:outline-none">
        @yield('content')
    </main>

    <x-site.footer :menus="$footerMenus" :site="$site" />
</body>
</html>
