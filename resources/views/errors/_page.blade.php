@extends('layouts.site', ['seo' => \App\Support\Seo\Seo::make($title)->noindex()])

@section('content')
    <section class="surface-dark relative overflow-hidden">
        <div class="grid-lines pointer-events-none absolute inset-0 [mask-image:radial-gradient(ellipse_at_center,black_20%,transparent_70%)]" aria-hidden="true"></div>
        <div class="container-dx relative grid min-h-[70vh] items-center gap-12 py-20 lg:grid-cols-12">
            <div class="lg:col-span-7">
                <p class="eyebrow accent">Error {{ $code }}</p>
                <h1 class="display-lg mt-6">{{ $title }}</h1>
                <p class="lede text-muted mt-6 max-w-xl">{{ $message }}</p>
                <div class="mt-10 flex flex-wrap gap-3">
                    <a href="{{ route('home') }}" class="btn btn-primary">Back to the homepage <x-icon name="arrow-right" :size="18" class="btn-arrow" /></a>
                    @if ($code === 404)
                        <a href="{{ route('contact') }}" class="btn btn-secondary">Contact us</a>
                    @endif
                </div>
                @if ($code === 404)
                    <form method="GET" action="{{ route('search') }}" role="search" class="mt-12 flex max-w-xl gap-3">
                        <label for="error-search" class="sr-only">Search the site</label>
                        <input id="error-search" type="search" name="q" class="field" placeholder="Search the site" minlength="2" maxlength="100" required>
                        <button type="submit" class="btn btn-secondary" aria-label="Search"><x-icon name="search" :size="18" /></button>
                    </form>
                @endif
            </div>
            <div class="hidden lg:col-span-5 lg:block" aria-hidden="true">
                <p class="text-right font-display text-[14rem] font-semibold leading-none text-line-strong">{{ $code }}</p>
            </div>
        </div>
    </section>
@endsection
