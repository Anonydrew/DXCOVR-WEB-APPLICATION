@php
    $user = auth()->user();
    $navigation = \App\Support\AdminNavigation::for($user);
    $siteName = app(\App\Services\SiteSettings::class)->get('site.name');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ trim($__env->yieldContent('title', 'Admin')) }} · {{ $siteName }} admin</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
</head>
<body class="min-h-dvh" x-data="{ sidebar: false }">
    <a href="#admin-main" class="sr-only-focusable left-4 top-4 z-[100] rounded-lg bg-lime px-4 py-2 font-semibold text-ink">Skip to content</a>

    <div class="flex min-h-dvh">
        {{-- Sidebar --}}
        <div x-show="sidebar" x-transition.opacity class="fixed inset-0 z-30 bg-black/60 lg:hidden" @click="sidebar = false" x-cloak></div>
        <aside class="a-sidebar fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col transition-transform lg:sticky lg:top-0 lg:h-dvh lg:translate-x-0"
               :class="sidebar && 'translate-x-0'" aria-label="Admin navigation">
            <div class="flex h-16 flex-none items-center justify-between border-b border-line px-5">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 font-display font-bold tracking-[0.08em]">
                    <x-site.logo :label="$siteName" class="text-base" />
                </a>
                <button type="button" class="a-icon-btn lg:hidden" @click="sidebar = false" aria-label="Close navigation"><x-icon name="close" :size="18" /></button>
            </div>
            <nav class="flex-1 overflow-y-auto px-3 pb-6">
                @foreach ($navigation as $group)
                    <p class="a-nav-group">{{ $group['label'] }}</p>
                    <ul class="space-y-0.5">
                        @foreach ($group['items'] as $item)
                            <li>
                                <a href="{{ route($item['route'], $item['params'] ?? []) }}" class="a-nav-link" @if (\App\Support\AdminNavigation::isActive($item)) aria-current="page" @endif>
                                    <x-icon :name="$item['icon']" :size="18" />
                                    {{ $item['label'] }}
                                    @if (! empty($item['count']))
                                        <span class="a-nav-count" aria-label="{{ $item['count'] }} need attention">{{ $item['count'] > 99 ? '99+' : $item['count'] }}</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endforeach
            </nav>
            <div class="flex-none border-t border-line p-3">
                <a href="{{ route('home') }}" target="_blank" rel="noopener" class="a-nav-link"><x-icon name="external" :size="18" /> View website</a>
            </div>
        </aside>

        {{-- Main --}}
        <div class="flex min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-20 flex h-16 flex-none items-center gap-3 border-b border-line bg-ink/90 px-4 backdrop-blur sm:px-6">
                <button type="button" class="a-icon-btn lg:hidden" @click="sidebar = true" aria-label="Open navigation"><x-icon name="menu" :size="20" /></button>
                <div class="min-w-0 flex-1 truncate text-sm a-muted">@yield('breadcrumb')</div>
                <div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false">
                    <button type="button" class="flex items-center gap-2.5 rounded-lg px-2 py-1.5 hover:bg-white/5" @click="open = ! open" :aria-expanded="open" aria-haspopup="menu">
                        <span class="grid size-8 place-items-center rounded-full bg-surface-3 text-xs font-bold">{{ $user->initials() }}</span>
                        <span class="hidden text-left sm:block">
                            <span class="block text-sm font-semibold leading-tight">{{ $user->name }}</span>
                            <span class="block text-xs a-subtle">{{ $user->roles->pluck('name')->first() ?? 'No role' }}</span>
                        </span>
                        <x-icon name="chevron-down" :size="14" class="a-subtle" />
                    </button>
                    <div x-show="open" x-transition.origin.top.right x-cloak class="a-card absolute right-0 mt-2 w-56 p-1.5 shadow-2xl" role="menu">
                        <a href="{{ route('admin.account') }}" class="a-nav-link" role="menuitem"><x-icon name="user" :size="16" /> Account & security</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="a-nav-link w-full" role="menuitem"><x-icon name="logout" :size="16" /> Sign out</button>
                        </form>
                    </div>
                </div>
            </header>

            <main id="admin-main" class="flex-1 px-4 py-6 sm:px-6 lg:px-8 lg:py-8" tabindex="-1">
                @if (session('status'))
                    <div class="a-alert a-alert-success mb-6" role="status"><x-icon name="check" :size="18" class="mt-0.5 flex-none text-lime" /> <div>{{ session('status') }}</div></div>
                @endif
                @if (session('warning'))
                    <div class="a-alert a-alert-warning mb-6" role="status"><x-icon name="alert" :size="18" class="mt-0.5 flex-none text-warning" /> <div>{{ session('warning') }}</div></div>
                @endif
                @if (session('error'))
                    <div class="a-alert a-alert-error mb-6" role="alert"><x-icon name="alert" :size="18" class="mt-0.5 flex-none text-danger" /> <div>{{ session('error') }}</div></div>
                @endif
                @if ($errors->has('publication'))
                    <div class="a-alert a-alert-warning mb-6" role="alert">
                        <x-icon name="alert" :size="18" class="mt-0.5 flex-none text-warning" />
                        <div>
                            <p class="font-semibold">This cannot be published yet</p>
                            <ul class="mt-1 list-disc pl-5">
                                @foreach ($errors->get('publication') as $message)
                                    @foreach ((array) $message as $line)
                                        <li>{{ $line }}</li>
                                    @endforeach
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    {{-- Toasts --}}
    <div class="pointer-events-none fixed bottom-4 right-4 z-[60] flex w-80 flex-col gap-2" aria-live="polite">
        <template x-for="toast in $store.toast.items" :key="toast.id">
            <div class="a-alert pointer-events-auto shadow-xl" :class="toast.tone === 'error' ? 'a-alert-error bg-surface' : 'a-alert-success bg-surface'">
                <span x-text="toast.message" class="flex-1"></span>
                <button type="button" @click="$store.toast.dismiss(toast.id)" class="a-subtle" aria-label="Dismiss"><x-icon name="close" :size="14" /></button>
            </div>
        </template>
    </div>

    @can('media.view')
        <x-admin.media-dialog />
    @endcan
</body>
</html>
