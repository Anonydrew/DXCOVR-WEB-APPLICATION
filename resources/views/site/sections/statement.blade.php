<section class="surface-light section">
    <div class="container-dx grid gap-10 lg:grid-cols-12">
        <div class="lg:col-span-3">
            @if ($section->value('eyebrow'))
                <p class="eyebrow accent" data-reveal>{{ $section->value('eyebrow') }}</p>
            @endif
        </div>
        <div class="lg:col-span-9">
            <p class="display-md max-w-5xl" data-reveal>{{ \App\Support\Text::emphasis($section->value('statement')) }}</p>
            @if ($section->value('body'))
                <div class="mt-10 grid gap-6 text-paper-muted lg:grid-cols-2" data-reveal style="--reveal-delay:.08s">
                    <div class="lede space-y-5 lg:col-start-2">{{ \App\Support\Text::paragraphs($section->value('body')) }}</div>
                </div>
            @endif
        </div>
    </div>
</section>
