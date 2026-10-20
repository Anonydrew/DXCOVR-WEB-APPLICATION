@extends('layouts.site')

@section('content')
    <section class="about-hero" aria-labelledby="about-heading">
        <div class="container-dx">
            <p class="font-semibold text-lime" data-reveal>About {{ $site->get('site.name') }}</p>
            <h1 id="about-heading" class="about-hero-title mt-6" data-reveal style="--reveal-delay:.05s">{{ $page?->title ?? 'About DXCOVR' }}</h1>
            @if ($page?->excerpt)
                <p class="mt-10 max-w-4xl text-lg font-semibold leading-relaxed" data-reveal style="--reveal-delay:.1s">{{ $page->excerpt }}</p>
            @endif
        </div>
    </section>

    @include('site.sections._render')

    @if ($sections->isEmpty())
        <section class="surface-light section">
            <div class="container-dx">
                <p class="lede max-w-3xl text-paper-muted">{{ $site->get('site.description') }}</p>
            </div>
        </section>
    @endif
@endsection
