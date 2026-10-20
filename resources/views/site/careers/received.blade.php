@extends('layouts.site')

@section('content')
    <section class="surface-dark section relative overflow-hidden">
        <div class="glow-lime pointer-events-none absolute left-1/2 top-0 size-[40rem] -translate-x-1/2 opacity-60" aria-hidden="true"></div>
        <div class="container-dx relative max-w-3xl text-center">
            <span class="mx-auto grid size-16 place-items-center rounded-full bg-lime text-ink"><x-icon name="check" :size="28" /></span>
            <h1 class="display-md mt-8">Application received</h1>
            <p class="lede text-muted mt-6">Thank you for applying for <strong class="text-text">{{ $application['role'] }}</strong>. We have safely received your application and will be in touch about next steps.</p>
            <p class="mt-6 text-sm text-subtle">Your reference: <span class="font-mono text-text">{{ $application['reference'] }}</span></p>
            <div class="mt-10 flex flex-wrap justify-center gap-3">
                <a href="{{ route('careers.index') }}" class="btn btn-secondary">See other roles</a>
                <a href="{{ route('home') }}" class="btn btn-primary">Back to the homepage</a>
            </div>
        </div>
    </section>
@endsection
