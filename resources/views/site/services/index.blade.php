@extends('layouts.site')

@section('content')
    <x-site.feature-hero key="services" :page="$page" :title="$page?->title ?? 'Services'" :lede="$page?->excerpt" :default-cta-url="route('contact')" />

    <section class="surface-light service-features" aria-label="Our services">
        <div class="service-features-inner">
            @if ($services->isEmpty())
                <x-site.empty-state title="Our services are being updated">
                    We are refreshing how we describe what we do. In the meantime, tell us about your project and we will tell you honestly whether we can help.
                    <x-slot:actions>
                        <a href="{{ route('contact') }}" class="btn btn-primary">Start a conversation</a>
                    </x-slot:actions>
                </x-site.empty-state>
            @else
                @foreach ($services as $service)
                    <article id="{{ $service->slug }}" class="service-feature" aria-labelledby="service-{{ $service->id }}">
                        <div class="service-feature-intro" data-reveal>
                            <h2 id="service-{{ $service->id }}" class="service-feature-title">
                                <a href="{{ route('services.show', $service) }}" class="transition-opacity hover:opacity-75">{{ $service->title }}</a>
                            </h2>
                            <p class="mt-7 text-xl leading-relaxed">{{ $service->short_description }}</p>
                        </div>

                        <div class="service-feature-media" data-reveal style="--reveal-delay:.08s">
                            @if ($service->loopVideo)
                                <video class="h-full w-full object-cover" muted loop playsinline preload="metadata" aria-hidden="true" data-loop-video
                                       @if ($service->heroImage) poster="{{ $service->heroImage->variantUrl(1600) }}" @endif>
                                    <source src="{{ $service->loopVideo->url() }}" type="{{ $service->loopVideo->mime_type }}">
                                </video>
                                <button type="button" class="video-toggle" data-video-toggle aria-pressed="false" hidden>
                                    <x-icon name="pause" :size="16" data-video-icon="pause" />
                                    <x-icon name="play" :size="14" data-video-icon="play" class="hidden" />
                                    <span class="sr-only" data-video-label>Pause video</span>
                                </button>
                            @elseif ($service->heroImage)
                                <x-site.picture :media="$service->heroImage" sizes="(min-width: 1024px) 55vw, 100vw" class="h-full w-full object-cover" />
                            @else
                                <x-site.cover-art :seed="$service->slug" :glyph="$service->icon" :label="$service->title" />
                            @endif
                        </div>

                        <div class="service-feature-details" data-reveal>
                            @if (! empty($service->capabilities))
                                <ul class="service-feature-list" aria-label="{{ $service->title }}: what is included">
                                    @foreach ($service->capabilities as $capability)
                                        <li>{{ $capability }}</li>
                                    @endforeach
                                </ul>
                            @endif
                            <a href="{{ route('services.show', $service) }}" class="link-arrow mt-8">
                                Explore the service<span class="sr-only">: {{ $service->title }}</span>
                                <x-icon name="arrow-right" :size="18" class="btn-arrow accent" />
                            </a>
                        </div>
                    </article>
                @endforeach
            @endif
        </div>
    </section>

    @include('site.sections._render')
@endsection
