@extends('layouts.site')

@section('content')
    @include('site.partials.preview-banner', ['status' => $product->status->label()])

    <section class="surface-dark relative overflow-hidden">
        <div class="container-dx relative pt-12 sm:pt-16">
            <x-site.breadcrumbs :items="$seo->breadcrumbItems()" />
            <div class="mt-10 grid items-end gap-10 lg:grid-cols-12">
                <div class="lg:col-span-8">
                    <div class="flex flex-wrap items-center gap-4">
                        @if ($product->logo)
                            <x-site.picture :media="$product->logo" sizes="64px" class="size-14 rounded-2xl object-contain" alt="{{ $product->name }} logo" />
                        @endif
                        <span class="badge {{ $product->product_status === \App\Enums\ProductStatus::Available ? 'badge-lime' : '' }}">{{ $product->product_status->label() }}</span>
                    </div>
                    <h1 class="display-lg mt-6">{{ $product->name }}</h1>
                    @if ($product->tagline)
                        <p class="heading-lg mt-4 font-medium text-muted">{{ $product->tagline }}</p>
                    @endif
                    <p class="lede text-muted mt-6 max-w-3xl">{{ $product->short_description }}</p>
                </div>
                <div class="flex flex-wrap gap-3 lg:col-span-4 lg:justify-end">
                    @if ($product->acceptsEnquiries())
                        <a href="#product-enquiry" class="btn btn-primary">{{ $product->demo_requests_enabled ? 'Request a demo' : 'Enquire' }} <x-icon name="arrow-right" :size="18" class="btn-arrow" /></a>
                    @endif
                    @if ($product->documentation_url)
                        <a href="{{ $product->documentation_url }}" class="btn btn-secondary" target="_blank" rel="noopener">Documentation <x-icon name="arrow-up-right" :size="16" /></a>
                    @endif
                </div>
            </div>
            <div class="media-frame mt-14 aspect-[16/9] rounded-t-[var(--radius-card)] border border-b-0 border-line">
                @if ($product->heroImage)
                    <x-site.picture :media="$product->heroImage" :eager="true" sizes="(min-width: 1400px) 1360px, 100vw" class="h-full w-full object-cover object-top" />
                @else
                    <x-site.cover-art :seed="$product->slug" glyph="software" :label="$product->name" />
                @endif
            </div>
        </div>
    </section>

    @if (trim(strip_tags($body, '<img><iframe>')) !== '' || trim(strip_tags($audience)) !== '')
        <section class="surface-light section">
            <div class="container-dx grid gap-14 lg:grid-cols-12">
                @if (trim(strip_tags($body, '<img><iframe>')) !== '')
                    <div class="lg:col-span-7">
                        <p class="eyebrow accent">Overview</p>
                        <div class="prose-dx mt-6">{!! $body !!}</div>
                    </div>
                @endif
                @if (trim(strip_tags($audience)) !== '')
                    <aside class="lg:col-span-4 lg:col-start-9">
                        <div class="card p-7">
                            <h2 class="mono-label text-paper-muted">Who it is for</h2>
                            <div class="prose-dx mt-4 text-base">{!! $audience !!}</div>
                        </div>
                    </aside>
                @endif
            </div>
        </section>
    @endif

    @if (! empty($product->features))
        <section class="surface-dark section" aria-labelledby="product-features">
            <div class="container-dx">
                <x-site.section-heading id="product-features" eyebrow="Features" heading="What it does" />
                <ul class="mt-12 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($product->features as $feature)
                        <li class="card p-7" data-reveal style="--reveal-delay:{{ min($loop->index * 0.04, 0.3) }}s">
                            <x-icon name="check" :size="20" class="text-lime" />
                            <h3 class="heading-md mt-5">{{ $feature['title'] }}</h3>
                            @if (! empty($feature['body']))
                                <p class="mt-3 text-muted">{{ $feature['body'] }}</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    @if (! empty($product->modules))
        <section class="surface-raised section" aria-labelledby="product-modules">
            <div class="container-dx grid gap-12 lg:grid-cols-12">
                <div class="lg:col-span-4">
                    <p class="eyebrow accent">Modules</p>
                    <h2 id="product-modules" class="display-md mt-5">Built in parts that work together</h2>
                </div>
                <ol class="divide-y divide-line border-y border-line lg:col-span-8">
                    @foreach ($product->modules as $module)
                        <li class="grid gap-3 py-6 sm:grid-cols-12">
                            <span class="mono-label text-subtle sm:col-span-1">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <h3 class="heading-md sm:col-span-4">{{ $module['title'] }}</h3>
                            <p class="text-muted sm:col-span-7">{{ $module['body'] ?? '' }}</p>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>
    @endif

    @if ($screenshots->isNotEmpty())
        <section class="surface-dark section" aria-labelledby="screenshots">
            <div class="container-dx">
                <h2 id="screenshots" class="eyebrow accent">Screenshots</h2>
                <div class="mt-10 grid gap-6 md:grid-cols-2">
                    @foreach ($screenshots as $shot)
                        <figure class="{{ $loop->first ? 'md:col-span-2' : '' }}">
                            <div class="media-frame rounded-[var(--radius-card)] border border-line">
                                <x-site.picture :media="$shot->media" sizes="(min-width: 768px) 50vw, 100vw" class="w-full" />
                            </div>
                            @if ($shot->caption || $shot->media->caption)
                                <figcaption class="mt-3 text-sm text-subtle">{{ $shot->caption ?: $shot->media->caption }}</figcaption>
                            @endif
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if (! empty($product->integrations) || $pricing)
        <section class="surface-light section">
            <div class="container-dx grid gap-14 lg:grid-cols-12">
                @if (! empty($product->integrations))
                    <div class="lg:col-span-5">
                        <h2 class="heading-lg">Integrations</h2>
                        <ul class="mt-6 flex flex-wrap gap-2">
                            @foreach ($product->integrations as $integration)
                                <li class="chip">{{ $integration }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if ($pricing)
                    <div class="lg:col-span-6 lg:col-start-7">
                        <h2 class="heading-lg">Pricing</h2>
                        <div class="prose-dx mt-6">{!! $pricing !!}</div>
                    </div>
                @endif
            </div>
        </section>
    @endif

    @if ($product->projects->isNotEmpty())
        <section class="surface-dark section" aria-labelledby="product-work">
            <div class="container-dx">
                <x-site.section-heading id="product-work" eyebrow="In practice" heading="Related case studies" />
                <div class="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($product->projects as $project)
                        <x-site.project-card :project="$project" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($product->faqs->isNotEmpty())
        <section class="surface-raised section" aria-labelledby="product-faq">
            <div class="container-dx grid gap-12 lg:grid-cols-12">
                <div class="lg:col-span-4">
                    <p class="eyebrow accent">FAQ</p>
                    <h2 id="product-faq" class="display-md mt-5">Questions about {{ $product->name }}</h2>
                </div>
                <x-site.faq-list :faqs="$product->faqs" class="lg:col-span-8" />
            </div>
        </section>
    @endif

    @if ($product->acceptsEnquiries() && ! $isPreview)
        <section id="product-enquiry" class="surface-dark section border-t border-line" aria-labelledby="product-enquiry-heading">
            <div class="container-dx grid gap-12 lg:grid-cols-12">
                <div class="lg:col-span-5">
                    <p class="eyebrow accent">{{ $product->demo_requests_enabled ? 'Demo' : 'Enquiries' }}</p>
                    <h2 id="product-enquiry-heading" class="display-md mt-5">{{ $product->demo_requests_enabled ? 'See '.$product->name.' in action' : 'Ask us about '.$product->name }}</h2>
                    <p class="lede text-muted mt-6">Tell us a little about your organisation and what you need. We will get back to you to arrange the next step.</p>
                </div>
                <div class="card p-6 sm:p-10 lg:col-span-7">
                    <x-site.enquiry-form :product="$product" :type="$product->demo_requests_enabled ? 'demo' : 'product'" id="product-form" />
                </div>
            </div>
        </section>
    @endif
@endsection
