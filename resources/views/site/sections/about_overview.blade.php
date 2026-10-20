@php
    $stats = collect($section->value('stats', []))->filter(fn ($stat) => filled($stat['value'] ?? null));
@endphp
<section class="surface-light about-section" aria-label="Overview">
    <div class="container-dx">
        @if ($section->value('lead') || $section->value('body'))
            <div class="grid gap-6 md:grid-cols-12 md:gap-10">
                @if ($section->value('lead'))
                    <p class="text-lg font-semibold leading-relaxed md:col-span-4" data-reveal>{{ $section->value('lead') }}</p>
                @endif
                @if ($section->value('body'))
                    <p class="text-lg leading-relaxed text-paper-muted md:col-span-8" data-reveal style="--reveal-delay:.05s">{{ $section->value('body') }}</p>
                @endif
            </div>
        @endif

        @if ($stats->isNotEmpty())
            <ul class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($stats as $stat)
                    <li class="about-stat" data-reveal style="--reveal-delay:{{ min($loop->index * 0.05, 0.25) }}s">
                        <p class="about-stat-value">{{ $stat['value'] }}</p>
                        <p class="mt-3 text-lg font-medium text-paper-muted">{{ $stat['label'] }}</p>
                        @if (! empty($stat['note']))
                            <p class="mt-3 text-sm text-paper-muted">{{ $stat['note'] }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</section>
