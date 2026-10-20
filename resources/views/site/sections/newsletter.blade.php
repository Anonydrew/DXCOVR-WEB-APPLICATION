@php($site = app(\App\Services\SiteSettings::class))
@if ($site->get('newsletter.enabled'))
    <section class="surface-light section-tight" aria-labelledby="newsletter-{{ $section->id }}">
        <div class="container-dx grid items-center gap-10 lg:grid-cols-12">
            <div class="lg:col-span-6">
                <h2 id="newsletter-{{ $section->id }}" class="heading-lg">{{ $section->value('heading') ?: $site->get('newsletter.heading') }}</h2>
                <p class="mt-3 text-paper-muted">{{ $section->value('body') ?: $site->get('newsletter.body') }}</p>
            </div>
            <div class="lg:col-span-6">
                <x-site.newsletter-form :id="'newsletter-'.$section->id" />
            </div>
        </div>
    </section>
@endif
