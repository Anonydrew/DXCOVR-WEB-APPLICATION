@php($founder = $data['founder'] ?? null)
<section class="surface-raised section relative overflow-hidden" aria-labelledby="about-{{ $section->id }}">
    <div class="container-dx grid items-center gap-12 lg:grid-cols-12">
        <div class="lg:col-span-6">
            @if ($section->value('eyebrow'))
                <p class="eyebrow accent" data-reveal>{{ $section->value('eyebrow') }}</p>
            @endif
            @if ($section->value('heading'))
                <h2 id="about-{{ $section->id }}" class="display-md mt-5" data-reveal>{{ \App\Support\Text::emphasis($section->value('heading')) }}</h2>
            @endif
            @if ($section->value('body'))
                <div class="lede text-muted mt-6 space-y-5" data-reveal style="--reveal-delay:.06s">{{ \App\Support\Text::paragraphs($section->value('body')) }}</div>
            @endif
            @if ($section->value('cta_label') && $section->value('cta_url'))
                <a href="{{ $section->value('cta_url') }}" class="btn btn-secondary mt-9" data-reveal>
                    {{ $section->value('cta_label') }} <x-icon name="arrow-right" :size="18" class="btn-arrow" />
                </a>
            @endif
        </div>

        @if ($section->value('show_founder') && $founder)
            <div class="lg:col-span-5 lg:col-start-8" data-reveal style="--reveal-delay:.1s">
                <article class="card card-link group p-3">
                    <div class="media-frame aspect-[4/5] rounded-[calc(var(--radius-card)-0.5rem)]">
                        @if ($founder->photo)
                            <x-site.picture :media="$founder->photo" sizes="(min-width: 1024px) 34vw, 100vw" class="h-full w-full object-cover" />
                        @else
                            <div class="grid h-full place-items-center bg-surface-2 grid-lines">
                                <span class="font-display text-8xl font-semibold text-line-strong" aria-hidden="true">{{ $founder->initials() }}</span>
                            </div>
                        @endif
                    </div>
                    <div class="flex items-end justify-between gap-4 p-5">
                        <div>
                            <p class="mono-label accent">Founder</p>
                            <h3 class="heading-md mt-2">
                                <a href="{{ route('about.founder') }}" class="stretched-link">{{ $founder->name }}</a>
                            </h3>
                            @if ($founder->role_title)
                                <p class="mt-1 text-sm text-muted">{{ $founder->role_title }}</p>
                            @endif
                        </div>
                        <x-icon name="arrow-up-right" :size="22" class="flex-none text-muted transition group-hover:text-lime" />
                    </div>
                </article>
            </div>
        @endif
    </div>
</section>
