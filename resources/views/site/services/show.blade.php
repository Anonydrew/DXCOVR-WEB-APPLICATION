@extends('layouts.site')

@section('content')
    @include('site.partials.preview-banner', ['status' => $service->status->label()])

    <section class="surface-dark relative overflow-hidden">
        <div class="grid-lines pointer-events-none absolute inset-0 [mask-image:linear-gradient(to_bottom,black,transparent)]" aria-hidden="true"></div>
        <div class="glow-lime pointer-events-none absolute -right-40 -top-32 size-[36rem] opacity-60" aria-hidden="true"></div>
        <div class="container-dx relative pt-12 pb-16 sm:pt-16 sm:pb-24">
            <x-site.breadcrumbs :items="$seo->breadcrumbItems()" />
            <div class="mt-10 grid items-center gap-12 lg:grid-cols-12">
                <div class="lg:col-span-7">
                    <p class="eyebrow accent">Service</p>
                    <h1 class="display-lg mt-5">{{ $service->title }}</h1>
                    <p class="lede text-muted mt-6 max-w-2xl">{{ $service->short_description }}</p>
                    <div class="mt-10 flex flex-wrap gap-3">
                        <a href="{{ route('contact', ['service' => $service->slug]) }}" class="btn btn-primary">Discuss your project <x-icon name="arrow-right" :size="18" class="btn-arrow" /></a>
                        @if ($service->projects->isNotEmpty())
                            <a href="#related-work" class="btn btn-secondary">See related work</a>
                        @endif
                    </div>
                </div>
                <div class="lg:col-span-5">
                    @if ($video)
                        <div class="media-frame aspect-video rounded-[var(--radius-card)] border border-line">
                            <iframe src="{{ $video['src'] }}" title="{{ $service->title }} video" class="absolute inset-0 h-full w-full" loading="lazy" allow="accelerometer; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                        </div>
                    @elseif ($service->heroImage)
                        <div class="media-frame aspect-[4/3] rounded-[var(--radius-card)] border border-line">
                            <x-site.picture :media="$service->heroImage" :eager="true" sizes="(min-width: 1024px) 40vw, 100vw" class="h-full w-full object-cover" />
                        </div>
                    @else
                        <div class="relative mx-auto grid aspect-square max-w-sm place-items-center rounded-full border border-line text-text" aria-hidden="true">
                            <div class="absolute inset-8 rounded-full border border-dashed border-line-strong"></div>
                            <x-site.glyph :name="$service->icon" :size="180" />
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    @if (! empty($service->capabilities))
        <section class="surface-raised section-tight border-y border-line" aria-labelledby="capabilities">
            <div class="container-dx grid gap-10 lg:grid-cols-12">
                <h2 id="capabilities" class="eyebrow accent lg:col-span-3">Capabilities</h2>
                <ul class="grid gap-x-10 gap-y-4 sm:grid-cols-2 lg:col-span-9">
                    @foreach ($service->capabilities as $capability)
                        <li class="flex items-start gap-3 border-b border-line pb-4 font-display text-lg">
                            <x-icon name="check" :size="18" class="mt-1 flex-none text-lime" />
                            {{ $capability }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    @if (trim(strip_tags($body, '<img><iframe>')) !== '')
        <section class="surface-light section">
            <div class="container-dx grid gap-12 lg:grid-cols-12">
                <p class="eyebrow accent lg:col-span-3">Overview</p>
                <div class="prose-dx lg:col-span-8 lg:col-start-5">{!! $body !!}</div>
            </div>
        </section>
    @endif

    @if (! empty($service->use_cases))
        <section class="surface-dark section" aria-labelledby="use-cases">
            <div class="container-dx">
                <x-site.section-heading id="use-cases" eyebrow="Typical use cases" heading="Where this makes a difference" />
                <ul class="mt-12 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($service->use_cases as $useCase)
                        <li class="card p-7" data-reveal style="--reveal-delay:{{ min($loop->index * 0.05, 0.3) }}s">
                            <span class="mono-label accent">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <h3 class="heading-md mt-5">{{ $useCase['title'] }}</h3>
                            @if (! empty($useCase['body']))
                                <p class="mt-3 text-muted">{{ $useCase['body'] }}</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    @if (! empty($service->process_steps))
        <section class="surface-raised section" aria-labelledby="process">
            <div class="container-dx">
                <x-site.section-heading id="process" eyebrow="Process" heading="How we deliver it" />
                <ol class="mt-12 grid gap-10 sm:grid-cols-2 {{ count($service->process_steps) >= 4 ? 'lg:grid-cols-4' : 'lg:grid-cols-3' }}">
                    @foreach ($service->process_steps as $step)
                        <li class="relative border-t border-line-strong pt-7">
                            <span class="absolute -top-px left-0 h-px w-16 bg-lime" aria-hidden="true"></span>
                            <span class="mono-label accent">Step {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <h3 class="heading-md mt-4">{{ $step['title'] }}</h3>
                            @if (! empty($step['body']))
                                <p class="mt-3 text-muted">{{ $step['body'] }}</p>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>
    @endif

    @if ($service->projects->isNotEmpty())
        <section id="related-work" class="surface-dark section" aria-labelledby="related-work-heading">
            <div class="container-dx">
                <x-site.section-heading id="related-work-heading" eyebrow="Related work" heading="Case studies" cta-label="All work" :cta-url="route('work.index', ['service' => $service->slug])" />
                <div class="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($service->projects as $project)
                        <x-site.project-card :project="$project" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($service->products->isNotEmpty())
        <section class="surface-raised section" aria-labelledby="related-products">
            <div class="container-dx">
                <x-site.section-heading id="related-products" eyebrow="Related products" heading="Built by DXCOVR" />
                <div class="mt-12 grid gap-6 md:grid-cols-2">
                    @foreach ($service->products as $product)
                        <x-site.product-card :product="$product" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($service->faqs->isNotEmpty())
        <section class="surface-dark section" aria-labelledby="service-faq">
            <div class="container-dx grid gap-12 lg:grid-cols-12">
                <div class="lg:col-span-4">
                    <p class="eyebrow accent">FAQ</p>
                    <h2 id="service-faq" class="display-md mt-5">Common questions</h2>
                </div>
                <x-site.faq-list :faqs="$service->faqs" class="lg:col-span-8" />
            </div>
        </section>
    @endif

    <section class="bg-ink py-6 sm:py-10">
        <div class="container-dx">
            <div class="relative overflow-hidden rounded-[calc(var(--radius-card)+0.5rem)] bg-lime px-7 py-14 text-ink sm:px-14 sm:py-20">
                <div class="relative grid gap-8 lg:grid-cols-12 lg:items-end">
                    <div class="lg:col-span-8">
                        <h2 class="display-md">{{ $service->cta_heading ?: 'Let’s build something that works.' }}</h2>
                        <p class="lede mt-5 max-w-2xl text-ink/80">{{ $service->cta_body ?: 'Tell us about the problem you are solving. We will respond with honest advice on scope, approach and next steps.' }}</p>
                    </div>
                    <div class="lg:col-span-4 lg:flex lg:justify-end">
                        <a href="{{ $service->cta_url ?: route('contact', ['service' => $service->slug]) }}" class="btn btn-dark focus-visible:outline-ink">
                            {{ $service->cta_label ?: 'Start a project' }} <x-icon name="arrow-right" :size="18" class="btn-arrow" />
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if ($otherServices->isNotEmpty())
        <nav class="surface-dark section-tight" aria-label="Other services">
            <div class="container-dx">
                <p class="mono-label text-subtle">Other services</p>
                <ul class="mt-6 flex flex-wrap gap-2">
                    @foreach ($otherServices as $other)
                        <li><a href="{{ route('services.show', $other) }}" class="chip">{{ $other->title }}</a></li>
                    @endforeach
                </ul>
            </div>
        </nav>
    @endif
@endsection
