@if ($section->value('layout') === 'list')
    {{-- List layout: wide light cards, image on the left (used on the About page). --}}
    <section class="surface-light about-section" aria-labelledby="insights-{{ $section->id }}">
        <div class="container-dx">
            <h2 id="insights-{{ $section->id }}" class="about-heading" data-reveal>{{ $section->value('heading') ?: 'Latest updates' }}</h2>
            @if ($section->value('intro'))
                <p class="mt-6 max-w-3xl text-lg leading-relaxed text-paper-muted" data-reveal>{{ $section->value('intro') }}</p>
            @endif

            <ul class="mt-12 space-y-4">
                @foreach ($data['articles'] as $article)
                    <li data-reveal><x-site.story-card :article="$article" class="story-card-light" :excerpt-lines="2" /></li>
                @endforeach
            </ul>

            @if ($section->value('cta_label') && $section->value('cta_url'))
                <a href="{{ $section->value('cta_url') }}" class="link-arrow mt-10">
                    {{ $section->value('cta_label') }}
                    <x-icon name="arrow-right" :size="18" class="btn-arrow accent" />
                </a>
            @endif
        </div>
    </section>
@else
    <section class="surface-light section" aria-labelledby="insights-{{ $section->id }}">
        <div class="container-dx">
            <x-site.section-heading
                :id="'insights-'.$section->id"
                :eyebrow="$section->value('eyebrow')"
                :heading="$section->value('heading')"
                :intro="$section->value('intro')"
                :cta-label="$section->value('cta_label')"
                :cta-url="$section->value('cta_url')"
            />

            <div class="mt-14 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($data['articles'] as $article)
                    <div data-reveal style="--reveal-delay:{{ $loop->index * 0.06 }}s">
                        <x-site.article-card :article="$article" />
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
