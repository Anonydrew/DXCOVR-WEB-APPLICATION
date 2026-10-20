<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title') · {{ app(\App\Services\SiteSettings::class)->get('site.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
</head>
<body class="relative grid min-h-dvh place-items-center overflow-hidden px-4 py-12">
    <div class="pointer-events-none absolute -right-40 -top-40 size-[36rem] rounded-full bg-[radial-gradient(closest-side,rgb(166_236_70/0.16),transparent_70%)]" aria-hidden="true"></div>
    <main class="relative w-full max-w-md">
        <div class="mb-8 flex justify-center">
            <x-site.logo :href="route('home')" :label="app(\App\Services\SiteSettings::class)->get('site.name')" class="text-xl" />
        </div>
        <div class="a-card p-6 sm:p-8">
            @yield('content')
        </div>
        <p class="mt-6 text-center text-xs a-subtle">Authorised staff only. Activity on this system is logged.</p>
    </main>
</body>
</html>
