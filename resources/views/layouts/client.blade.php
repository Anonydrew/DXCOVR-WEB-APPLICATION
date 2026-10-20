@php($siteName = app(\App\Services\SiteSettings::class)->get('site.name'))
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · {{ $siteName }}</title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    <meta name="theme-color" content="#080A08">
    <meta name="csp-nonce" content="{{ Vite::cspNonce() }}">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="32x32">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    <script nonce="{{ Vite::cspNonce() }}">document.documentElement.classList.add('js');</script>
    @vite(['resources/css/site.css', 'resources/js/site.js'])
</head>
<body class="surface-dark flex min-h-dvh flex-col">
    <a href="#main" class="sr-only-focusable left-4 top-4 z-[100] rounded-full bg-lime px-5 py-3 font-semibold text-ink">Skip to main content</a>

    <header class="border-b border-line">
        <div class="container-dx flex h-[4.5rem] items-center justify-between gap-4">
            <x-site.logo :href="route('home')" :label="$siteName" />
            <p class="mono-label text-subtle">Client onboarding</p>
        </div>
    </header>

    <main id="main" tabindex="-1" class="flex-1 focus:outline-none">
        @yield('content')
    </main>

    <footer class="border-t border-line">
        <div class="container-dx flex flex-wrap items-center justify-between gap-3 py-6 text-sm text-subtle">
            <p class="flex items-center gap-2"><x-icon name="lock" :size="15" /> Private to you and the {{ $siteName }} team. Please do not share this link.</p>
            <p>&copy; {{ now()->year }} {{ $siteName }}</p>
        </div>
    </footer>
</body>
</html>
