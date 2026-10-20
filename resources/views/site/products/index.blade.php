@extends('layouts.site')

@section('content')
    <x-site.page-hero
        eyebrow="Products"
        :title="$page?->title ?? 'Products by DXCOVR'"
        :lede="$page?->excerpt"
        :breadcrumbs="$seo->breadcrumbItems()"
        glyph="software"
    />

    <section class="surface-dark pb-24 sm:pb-32" aria-label="Products">
        <div class="container-dx">
            @if ($products->isEmpty())
                <x-site.empty-state title="Products coming soon">
                    We are preparing our product pages. Get in touch if you would like an early look at what we are building.
                    <x-slot:actions>
                        <a href="{{ route('contact') }}" class="btn btn-primary">Get in touch</a>
                    </x-slot:actions>
                </x-site.empty-state>
            @else
                <div class="grid gap-6 md:grid-cols-2">
                    @foreach ($products as $product)
                        <div class="{{ $loop->first && $products->count() % 2 === 1 ? 'md:col-span-2' : '' }}" data-reveal style="--reveal-delay:{{ min($loop->index * 0.05, 0.25) }}s">
                            <x-site.product-card :product="$product" heading-level="h2" />
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
