@extends('layouts.site')

@section('content')
    <section class="surface-dark section relative overflow-hidden">
        <div class="glow-lime pointer-events-none absolute left-1/2 top-0 size-[40rem] -translate-x-1/2 opacity-60" aria-hidden="true"></div>
        <div class="container-dx relative max-w-3xl text-center">
            <span class="mx-auto grid size-16 place-items-center rounded-full bg-lime text-ink"><x-icon name="check" :size="28" /></span>
            <h1 class="display-md mt-8">Thank you, {{ \Illuminate\Support\Str::before($enquiry['name'], ' ') }}.</h1>
            <p class="lede text-muted mt-6">Your message has reached our team. We read every enquiry and will reply to the email address you provided.</p>
            <p class="mt-6 text-sm text-subtle">Reference: <span class="font-mono text-text">{{ $enquiry['reference'] }}</span></p>
            <div class="mt-10 flex flex-wrap justify-center gap-3">
                <a href="{{ route('work.index') }}" class="btn btn-secondary">Explore our work</a>
                <a href="{{ route('insights.index') }}" class="btn btn-primary">Read our insights</a>
            </div>
        </div>
    </section>
@endsection
