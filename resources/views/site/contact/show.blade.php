@extends('layouts.site')

@section('content')
    @php
        $email = $site->get('contact.email');
        $phone = $site->get('contact.phone');
        $whatsapp = $site->whatsappUrl();
        $hours = $site->get('contact.hours');
        $socials = \App\Support\SocialNetworks::profiles($site->get('social.links', []));
    @endphp

    <x-site.page-hero
        eyebrow="Contact"
        :title="$page?->title ?? 'Let’s talk about what you are building'"
        :lede="$page?->excerpt"
        :breadcrumbs="$seo->breadcrumbItems()"
    />

    <section class="surface-dark pb-24 sm:pb-32">
        <div class="container-dx grid gap-12 lg:grid-cols-12">
            <aside class="space-y-10 lg:col-span-4">
                <div>
                    <h2 class="mono-label text-subtle">Reach us directly</h2>
                    <ul class="mt-5 space-y-4">
                        @if ($email)
                            <li><a href="mailto:{{ $email }}" class="link-arrow text-lg"><x-icon name="mail" :size="20" class="accent" /> {{ $email }}</a></li>
                        @endif
                        @if ($phone)
                            <li><a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="link-arrow text-lg"><x-icon name="phone" :size="20" class="accent" /> {{ $phone }}</a></li>
                        @endif
                        @if ($whatsapp)
                            <li><a href="{{ $whatsapp }}" class="link-arrow text-lg" target="_blank" rel="noopener"><x-icon name="whatsapp" :size="20" class="accent" /> Chat on WhatsApp</a></li>
                        @endif
                        <li class="flex items-start gap-2 text-muted"><x-icon name="map-pin" :size="20" class="mt-0.5 flex-none accent" /> <span>{{ $site->get('contact.address') ?: $site->get('contact.location_summary') }}</span></li>
                        @if ($hours)
                            <li class="flex items-start gap-2 text-muted"><x-icon name="clock" :size="20" class="mt-0.5 flex-none accent" /> <span>{{ $hours }}</span></li>
                        @endif
                    </ul>
                </div>

                <div class="card p-6">
                    <h2 class="heading-md">What happens next</h2>
                    <ol class="mt-5 space-y-4 text-sm text-muted">
                        <li class="flex gap-3"><span class="mono-label accent">01</span> We read every enquiry and reply personally.</li>
                        <li class="flex gap-3"><span class="mono-label accent">02</span> If it is a fit, we arrange a short call to understand the problem.</li>
                        <li class="flex gap-3"><span class="mono-label accent">03</span> We follow up with a recommended approach, scope and next steps.</li>
                    </ol>
                </div>

                @if ($socials->isNotEmpty())
                    <div>
                        <h2 class="mono-label text-subtle">Elsewhere</h2>
                        <ul class="mt-4 flex flex-wrap gap-2">
                            @foreach ($socials as $social)
                                <li><a href="{{ $social['url'] }}" class="chip" target="_blank" rel="noopener me"><x-site.social-icon :profile="$social" :size="14" /> {{ \App\Support\SocialNetworks::label($social) }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </aside>

            <div class="lg:col-span-8">
                <div class="card p-6 sm:p-10">
                    <h2 class="heading-lg">Send us a message</h2>
                    <p class="mt-2 text-muted">Fields marked <span class="accent">*</span> are required.</p>
                    <div class="mt-8">
                        <x-site.enquiry-form :services="$services" :type="$initialType" :selected-service="$preselectedService" id="contact-form" />
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
