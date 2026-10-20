@props(['items' => [], 'site'])
@php($contactUrl = route('contact'))
<header class="site-header" data-site-header data-scrolled="false">
    <div class="container-dx flex h-[4.5rem] items-center gap-6">
        <x-site.logo :href="route('home')" :label="$site->get('site.name')" class="text-text" />

        <nav aria-label="Main" class="ml-auto hidden lg:block">
            <ul class="flex items-center gap-1">
                @foreach ($items as $index => $item)
                    @php($active = \App\Services\Navigation::isActive($item))
                    <li class="relative" @if ($item['children']) data-nav-item @endif>
                        @if ($item['children'])
                            <button type="button" class="nav-link" data-nav-toggle aria-expanded="false" aria-controls="nav-panel-{{ $index }}" @if ($active) aria-current="page" @endif>
                                {{ $item['label'] }}
                                <x-icon name="chevron-down" :size="14" class="opacity-70" />
                            </button>
                            <div id="nav-panel-{{ $index }}" class="nav-panel surface-light p-3" data-open="false">
                                <div class="grid gap-1 sm:grid-cols-2">
                                    @foreach ($item['children'] as $child)
                                        <a href="{{ $child['href'] }}" class="nav-panel-item" @if ($child['new_tab']) target="_blank" rel="noopener" @endif>
                                            <span class="block font-display font-semibold text-ink-text">{{ $child['label'] }}</span>
                                            @if ($child['description'])
                                                <span class="mt-1 block text-sm leading-snug text-paper-muted">{{ $child['description'] }}</span>
                                            @endif
                                        </a>
                                    @endforeach
                                </div>
                                <div class="mt-2 flex items-center justify-between gap-4 rounded-xl border border-paper-line bg-paper px-4 py-3">
                                    <span class="text-sm text-paper-muted">Not sure where your idea fits?</span>
                                    <a href="{{ $item['href'] }}" class="link-arrow text-sm font-semibold text-lime-deep">
                                        View all {{ \Illuminate\Support\Str::lower($item['label']) }}
                                        <x-icon name="arrow-right" :size="16" class="btn-arrow" />
                                    </a>
                                </div>
                            </div>
                        @else
                            <a href="{{ $item['href'] }}" class="nav-link" @if ($active) aria-current="page" @endif @if ($item['new_tab']) target="_blank" rel="noopener" @endif>{{ $item['label'] }}</a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="ml-auto flex items-center gap-2 lg:ml-2">
            <a href="{{ route('search') }}" class="grid size-11 place-items-center rounded-full text-muted transition hover:bg-white/5 hover:text-text" aria-label="Search the site">
                <x-icon name="search" :size="20" />
            </a>
            <a href="{{ $contactUrl }}" class="btn btn-white btn-sm hidden sm:inline-flex">
                Start a project
                <x-icon name="arrow-right" :size="16" class="btn-arrow" />
            </a>
            <button type="button" class="grid size-11 place-items-center rounded-full border border-field text-text lg:hidden" data-drawer-open aria-expanded="false" aria-controls="mobile-menu" aria-label="Open menu">
                <x-icon name="menu" :size="20" />
            </button>
        </div>
    </div>

    {{-- Mobile navigation drawer --}}
    <div id="mobile-menu" class="mobile-drawer lg:hidden" data-drawer hidden role="dialog" aria-modal="true" aria-label="Menu">
        <div class="container-dx flex h-[4.5rem] flex-none items-center justify-between">
            <x-site.logo :href="route('home')" :label="$site->get('site.name')" class="text-text" />
            <button type="button" class="grid size-11 place-items-center rounded-full border border-field" data-drawer-close aria-label="Close menu">
                <x-icon name="close" :size="20" />
            </button>
        </div>
        <nav aria-label="Mobile" class="container-dx flex-1 py-6">
            <ul class="divide-y divide-line border-y border-line">
                @foreach ($items as $index => $item)
                    <li>
                        @if ($item['children'])
                            <details class="disclosure group">
                                <summary class="flex items-center justify-between py-4 font-display text-2xl font-semibold">
                                    {{ $item['label'] }}
                                    <x-icon name="plus" :size="22" class="disclosure-icon text-lime" />
                                </summary>
                                <ul class="space-y-1 pb-5">
                                    <li><a href="{{ $item['href'] }}" class="block py-2 font-semibold text-lime">All {{ \Illuminate\Support\Str::lower($item['label']) }}</a></li>
                                    @foreach ($item['children'] as $child)
                                        <li><a href="{{ $child['href'] }}" class="block py-2 text-muted hover:text-text">{{ $child['label'] }}</a></li>
                                    @endforeach
                                </ul>
                            </details>
                        @else
                            <a href="{{ $item['href'] }}" class="block py-4 font-display text-2xl font-semibold" @if (\App\Services\Navigation::isActive($item)) aria-current="page" @endif>{{ $item['label'] }}</a>
                        @endif
                    </li>
                @endforeach
            </ul>
            <div class="mt-8 grid gap-3">
                <a href="{{ $contactUrl }}" class="btn btn-primary w-full">Start a project <x-icon name="arrow-right" :size="16" /></a>
                <a href="{{ route('search') }}" class="btn btn-secondary w-full"><x-icon name="search" :size="16" /> Search</a>
            </div>
        </nav>
    </div>
</header>
