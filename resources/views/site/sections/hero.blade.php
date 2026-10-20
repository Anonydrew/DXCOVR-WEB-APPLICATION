@php
    $media = $data['media'] ?? null;
    $library = $data['library'] ?? collect();
    $headingId = 'hero-'.$section->id;
    $showCards = (bool) $section->value('show_ui_cards', true);
    $proofItems = collect($section->value('proof_items', []))->filter(fn ($item) => filled($item['value'] ?? null))->values();
    $logos = collect($section->value('logos', []))
        ->map(fn ($logo) => array_merge($logo, ['media' => ! empty($logo['media_id']) ? $library->get((int) $logo['media_id']) : null]))
        ->filter(fn ($logo) => $logo['media'] !== null)
        ->values();
    $disciplines = collect($section->value('disciplines', []))->pluck('label')->filter()->values();
@endphp
<section class="hero surface-dark relative overflow-hidden" aria-labelledby="{{ $headingId }}">
    {{-- Stage: background image (or DXCOVR artwork) behind the headline. --}}
    <div class="hero-stage relative">
        <div class="hero-backdrop" aria-hidden="true">
            @if ($media)
                <x-site.picture :media="$media" :eager="true" sizes="100vw" alt="" class="hero-photo" />
            @else
                <div class="hero-artwork">
                    <div class="grid-lines absolute inset-0 opacity-70"></div>
                    <x-site.hero-visual :minimal="true" class="hero-artwork-visual" />
                </div>
            @endif
            <div class="hero-scrim"></div>
        </div>

        <div class="container-dx hero-content">
            <div class="max-w-[38rem]">
                @if ($section->value('eyebrow'))
                    <p class="eyebrow accent" data-reveal>{{ $section->value('eyebrow') }}</p>
                @endif
                <h1 id="{{ $headingId }}" class="hero-title mt-6" data-reveal style="--reveal-delay:.05s">{{ \App\Support\Text::emphasis($section->value('headline')) }}</h1>
                @if ($section->value('subheadline'))
                    <p class="lede mt-6 max-w-xl text-[#d6dad2]" data-reveal style="--reveal-delay:.1s">{{ $section->value('subheadline') }}</p>
                @endif

                <div class="mt-9 flex flex-col gap-3 sm:flex-row sm:flex-wrap" data-reveal style="--reveal-delay:.15s">
                    @if ($section->value('primary_label') && $section->value('primary_url'))
                        <a href="{{ $section->value('primary_url') }}" class="btn btn-primary hero-btn justify-center">
                            {{ $section->value('primary_label') }}
                            <x-icon name="arrow-right" :size="18" class="btn-arrow" />
                        </a>
                    @endif
                    @if ($section->value('secondary_label') && $section->value('secondary_url'))
                        <a href="{{ $section->value('secondary_url') }}" class="btn hero-btn hero-btn-outline justify-center">
                            {{ $section->value('secondary_label') }}
                        </a>
                    @endif
                </div>
            </div>
        </div>

        @if ($showCards)
            {{-- Illustrative interface cards. Decorative only: they show no real people, clients or data. --}}
            <div class="hero-cards" aria-hidden="true">
                <div class="hero-card hero-card-light">
                    <div class="flex items-center justify-between">
                        <p class="font-display text-[1.05rem] font-semibold">Your build</p>
                        <span class="flex gap-1"><i class="hero-dot"></i><i class="hero-dot"></i><i class="hero-dot"></i></span>
                    </div>
                    <ul class="mt-4 space-y-2.5">
                        @foreach ([['software', 'Software', 'hero-chip-lime'], ['ai', 'AI', 'hero-chip-blue'], ['hardware', 'IoT', 'hero-chip-neutral']] as [$glyph, $chip, $tone])
                            <li class="flex items-center gap-3 rounded-xl border border-paper-line px-3 py-2.5">
                                <span class="grid size-9 flex-none place-items-center rounded-full bg-paper-2 text-ink-text"><x-site.glyph :name="$glyph" :size="22" /></span>
                                <span class="flex-1 space-y-1.5"><i class="hero-bar w-24"></i><i class="hero-bar w-16 opacity-60"></i></span>
                                <span class="hero-chip {{ $tone }}">{{ $chip }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="hero-card hero-card-glass">
                    <div class="flex items-center gap-3">
                        <span class="grid size-11 flex-none place-items-center rounded-full bg-lime text-ink">
                            <svg width="18" height="18" viewBox="0 0 81.4 80.3" fill="currentColor"><path d="M65.25 64.3V80.3H0V0h65.25v16.02H16.31v48.3z" /><path d="M65.25 16.02H81.4v48.28H65.25z" /></svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block font-display font-semibold text-text">Your project</span>
                            <span class="block text-sm text-muted">From idea to launch</span>
                        </span>
                        <span class="hero-chip hero-chip-lime"><x-icon name="check" :size="12" /> Scoped</span>
                    </div>
                    <dl class="mt-5 space-y-2.5 text-sm">
                        <div class="flex justify-between gap-6"><dt class="mono-label text-muted">Track</dt><dd class="text-text">Software · AI · IoT</dd></div>
                        <div class="flex justify-between gap-6"><dt class="mono-label text-muted">Next step</dt><dd class="text-text">Prototype</dd></div>
                    </dl>
                </div>
            </div>
        @endif
    </div>

    {{-- Proof strip and logo row --}}
    @if ($section->value('proof_heading') || $proofItems->isNotEmpty() || $logos->isNotEmpty() || $disciplines->isNotEmpty())
        <div class="relative pb-14 sm:pb-16">
            <div class="hero-strip" data-motion-group>
                <div class="container-dx flex items-center justify-center gap-3">
                    @if ($section->value('proof_heading'))
                        <h2 class="hero-strip-label">{{ $section->value('proof_heading') }}</h2>
                    @endif
                    <button type="button" class="hero-motion-toggle" data-motion-toggle aria-pressed="false" hidden>
                        <x-icon name="pause" :size="14" data-motion-icon="pause" stroke-width="2.4" />
                        <x-icon name="play" :size="12" data-motion-icon="play" class="hidden" />
                        <span class="sr-only" data-motion-label>Pause scrolling</span>
                    </button>
                </div>

                @if ($proofItems->isNotEmpty())
                    @php($animateProof = $proofItems->count() >= 3)
                    <div class="hero-strip-viewport mt-8 {{ $animateProof ? 'is-animated' : 'is-static' }}">
                        <div class="hero-strip-track" style="--marquee-duration: {{ max(30, $proofItems->count() * 14) }}s">
                            <ul class="hero-strip-group" aria-label="{{ $section->value('proof_heading') ?: 'Highlights' }}">
                                @foreach ($proofItems as $item)
                                    @include('site.sections.partials.hero-proof-card', ['item' => $item, 'library' => $library])
                                @endforeach
                            </ul>
                            @if ($animateProof)
                                {{-- Duplicate for a seamless loop: hidden from assistive technology and keyboard focus. --}}
                                <ul class="hero-strip-group" aria-hidden="true" inert>
                                    @foreach ($proofItems as $item)
                                        @include('site.sections.partials.hero-proof-card', ['item' => $item, 'library' => $library])
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                @endif

                @if ($logos->isNotEmpty() || $disciplines->isNotEmpty())
                    <div class="hero-strip-viewport is-animated mt-8">
                        @if ($logos->isNotEmpty())
                            <ul class="sr-only" aria-label="Organisations we work with">
                                @foreach ($logos as $logo)
                                    <li>{{ $logo['name'] }}</li>
                                @endforeach
                            </ul>
                            <div class="hero-strip-track" style="--marquee-duration: {{ max(30, $logos->count() * 5) }}s" aria-hidden="true">
                                @foreach ([1, 2] as $copy)
                                    <div class="hero-strip-group items-center !gap-10 pr-10 lg:!gap-14 lg:pr-14">
                                        @foreach ($logos as $logo)
                                            <img src="{{ $logo['media']->variantUrl(480) }}" alt="" class="hero-logo" loading="lazy" decoding="async">
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <ul class="sr-only" aria-label="Disciplines">
                                @foreach ($disciplines as $discipline)
                                    <li>{{ $discipline }}</li>
                                @endforeach
                            </ul>
                            <div class="hero-strip-track" style="--marquee-duration: 50s" aria-hidden="true">
                                @foreach ([1, 2] as $copy)
                                    <div class="hero-strip-group items-center !gap-0">
                                        @foreach ($disciplines as $discipline)
                                            <span class="flex items-center gap-5 px-5 font-display text-base font-medium text-subtle sm:text-lg">
                                                {{ $discipline }}
                                                <span class="size-1.5 rounded-full bg-lime"></span>
                                            </span>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @endif
</section>
