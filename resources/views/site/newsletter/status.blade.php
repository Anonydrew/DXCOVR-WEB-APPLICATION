@extends('layouts.site')

@section('content')
    <section class="surface-dark section">
        <div class="container-dx max-w-2xl text-center">
            <x-site.glyph name="support" :size="72" class="mx-auto text-muted" />
            <h1 class="display-md mt-8">{{ $title }}</h1>
            <p class="lede text-muted mt-6">{{ $message }}</p>
            <div class="mt-10 flex flex-wrap justify-center gap-3">
                <a href="{{ route('insights.index') }}" class="btn btn-primary">Read our insights</a>
                @if ($unsubscribeToken)
                    <a href="{{ route('newsletter.unsubscribe', $unsubscribeToken) }}" class="btn btn-secondary">Unsubscribe</a>
                @endif
            </div>
        </div>
    </section>
@endsection
