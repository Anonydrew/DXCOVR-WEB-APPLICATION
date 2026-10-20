@extends('layouts.site')

@section('content')
    @if ($sections->isEmpty())
        {{-- Shown only before the homepage has been configured in Admin → Pages → Home. --}}
        <section class="surface-dark relative overflow-hidden">
            <div class="grid-lines pointer-events-none absolute inset-0 [mask-image:radial-gradient(ellipse_at_top_right,black_20%,transparent_70%)]" aria-hidden="true"></div>
            <div class="container-dx relative grid items-center gap-12 py-20 lg:grid-cols-12 lg:py-28">
                <div class="lg:col-span-7">
                    <p class="eyebrow accent">{{ $site->get('site.tagline') }}</p>
                    <h1 class="display-xl mt-6">We turn complex ideas into digital solutions <span class="accent">that work.</span></h1>
                    <p class="lede text-muted mt-8 max-w-2xl">{{ $site->get('site.description') }}</p>
                    <div class="mt-10 flex flex-wrap gap-3">
                        <a href="{{ route('work.index') }}" class="btn btn-primary">Explore our work <x-icon name="arrow-right" :size="18" class="btn-arrow" /></a>
                        <a href="{{ route('contact') }}" class="btn btn-secondary">Start a project</a>
                    </div>
                </div>
                <div class="lg:col-span-5"><x-site.hero-visual class="mx-auto max-w-[34rem]" /></div>
            </div>
        </section>
    @else
        @include('site.sections._render')
    @endif
@endsection
