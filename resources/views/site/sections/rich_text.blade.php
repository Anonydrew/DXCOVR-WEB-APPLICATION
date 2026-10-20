@php($content = app(\App\Services\RichContent::class)->prepare($section->value('body')))
<section class="surface-light section">
    <div class="container-dx grid gap-10 lg:grid-cols-12">
        <div class="lg:col-span-4">
            @if ($section->value('eyebrow'))
                <p class="eyebrow accent">{{ $section->value('eyebrow') }}</p>
            @endif
            @if ($section->value('heading'))
                <h2 class="display-md mt-5">{{ \App\Support\Text::emphasis($section->value('heading')) }}</h2>
            @endif
        </div>
        <div class="prose-dx lg:col-span-7 lg:col-start-6">{!! $content['html'] !!}</div>
    </div>
</section>
