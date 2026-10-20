@extends('layouts.client')

@section('title', 'Link expired')

@section('content')
    <section class="container-dx py-20 sm:py-28">
        <div class="mx-auto max-w-2xl text-center">
            <span class="mx-auto grid size-16 place-items-center rounded-full border border-line-strong text-muted" aria-hidden="true"><x-icon name="clock" :size="28" /></span>
            <h1 class="mt-8 font-display text-4xl font-semibold tracking-tight">This link has expired</h1>
            <p class="mt-5 text-lg leading-relaxed text-muted">For your security, questionnaire links only work for a limited time. Please get in touch and we will send you a new one. Anything you already saved is kept.</p>
            <a href="{{ route('contact') }}" class="btn btn-primary mt-10">Contact us <x-icon name="arrow-right" :size="18" class="btn-arrow" /></a>
        </div>
    </section>
@endsection
