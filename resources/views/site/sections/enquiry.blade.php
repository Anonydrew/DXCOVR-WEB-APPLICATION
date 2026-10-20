@php
    $site = app(\App\Services\SiteSettings::class);
    $whatsapp = $site->whatsappUrl();
    $email = $site->get('contact.email');
@endphp
<section id="start-a-project" class="surface-dark section relative overflow-hidden border-t border-line" aria-labelledby="enquiry-{{ $section->id }}">
    <div class="glow-lime pointer-events-none absolute -right-40 top-10 size-[36rem] opacity-70" aria-hidden="true"></div>
    <div class="container-dx relative grid gap-12 lg:grid-cols-12">
        <div class="lg:col-span-5">
            @if ($section->value('eyebrow'))
                <p class="eyebrow accent">{{ $section->value('eyebrow') }}</p>
            @endif
            <h2 id="enquiry-{{ $section->id }}" class="display-md mt-5">{{ \App\Support\Text::emphasis($section->value('heading') ?: 'Start a project') }}</h2>
            @if ($section->value('body'))
                <div class="lede text-muted mt-6 space-y-5">{{ \App\Support\Text::paragraphs($section->value('body')) }}</div>
            @endif
            @if ($email || $whatsapp)
                <ul class="mt-10 space-y-3">
                    @if ($email)
                        <li><a href="mailto:{{ $email }}" class="link-arrow"><x-icon name="mail" :size="18" class="accent" /> {{ $email }}</a></li>
                    @endif
                    @if ($whatsapp)
                        <li><a href="{{ $whatsapp }}" class="link-arrow" target="_blank" rel="noopener"><x-icon name="whatsapp" :size="18" class="accent" /> Message us on WhatsApp</a></li>
                    @endif
                </ul>
            @endif
        </div>
        <div class="lg:col-span-7">
            @if ($section->value('show_form', true))
                <div class="card p-6 sm:p-10">
                    <x-site.enquiry-form :services="$data['services'] ?? collect()" :compact="true" :id="'home-enquiry-'.$section->id" />
                </div>
            @else
                <a href="{{ route('contact') }}" class="btn btn-primary">Start a project <x-icon name="arrow-right" :size="18" class="btn-arrow" /></a>
            @endif
        </div>
    </div>
</section>
