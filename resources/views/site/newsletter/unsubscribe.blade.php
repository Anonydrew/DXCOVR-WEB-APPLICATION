@extends('layouts.site')

@section('content')
    <section class="surface-dark section">
        <div class="container-dx max-w-2xl text-center">
            @if ($valid)
                <h1 class="display-md">Unsubscribe from our newsletter?</h1>
                <p class="lede text-muted mt-6">You will stop receiving newsletter emails from us. You can subscribe again at any time.</p>
                <form method="POST" action="{{ route('newsletter.unsubscribe.confirm', $token) }}" class="mt-10">
                    @csrf
                    <button type="submit" class="btn btn-primary">Yes, unsubscribe me</button>
                </form>
            @else
                <h1 class="display-md">Nothing to unsubscribe</h1>
                <p class="lede text-muted mt-6">This link is not recognised, or the address has already been unsubscribed.</p>
                <a href="{{ route('home') }}" class="btn btn-secondary mt-10">Go to the homepage</a>
            @endif
        </div>
    </section>
@endsection
