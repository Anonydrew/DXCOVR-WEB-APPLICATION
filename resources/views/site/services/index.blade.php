@extends('layouts.site')

@section('content')
    <x-site.feature-hero key="services" :page="$page" :title="$page?->title ?? 'Services'" :lede="$page?->excerpt" :default-cta-url="route('contact')" />

    <section class="surface-dark pt-8 pb-24 sm:pt-12 sm:pb-32" aria-label="Service pillars">
        <div class="container-dx">
            @if ($services->isEmpty())
                <x-site.empty-state title="Our services are being updated">
                    We are refreshing how we describe what we do. In the meantime, tell us about your project and we will tell you honestly whether we can help.
                    <x-slot:actions>
                        <a href="{{ route('contact') }}" class="btn btn-primary">Start a conversation</a>
                    </x-slot:actions>
                </x-site.empty-state>
            @else
                <ol class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($services as $service)
                        <li class="card card-link group flex flex-col p-7 sm:p-8 {{ $loop->first ? 'lg:col-span-2' : '' }}" data-reveal style="--reveal-delay:{{ min($loop->index * 0.04, 0.3) }}s">
                            <div class="flex items-start justify-between gap-6">
                                <span class="mono-label text-subtle">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                <x-site.glyph :name="$service->icon" :size="$loop->first ? 96 : 64" class="text-muted transition duration-500 group-hover:text-text" />
                            </div>
                            <h2 class="{{ $loop->first ? 'display-md' : 'heading-lg' }} mt-8">
                                <a href="{{ route('services.show', $service) }}" class="stretched-link">{{ $service->title }}</a>
                            </h2>
                            <p class="mt-4 text-muted">{{ $service->short_description }}</p>
                            @if (! empty($service->capabilities))
                                <ul class="mt-6 flex flex-wrap gap-2" aria-label="Capabilities">
                                    @foreach (collect($service->capabilities)->take($loop->first ? 6 : 3) as $capability)
                                        <li class="chip">{{ $capability }}</li>
                                    @endforeach
                                </ul>
                            @endif
                            <span class="mt-auto inline-flex items-center gap-2 pt-8 text-sm font-semibold" aria-hidden="true">
                                Explore the service <x-icon name="arrow-right" :size="16" class="btn-arrow accent" />
                            </span>
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>
    </section>

    @include('site.sections._render')
@endsection
