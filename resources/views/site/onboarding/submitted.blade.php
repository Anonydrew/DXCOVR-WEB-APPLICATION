@extends('layouts.client')

@section('title', 'Thank you · '.$questionnaire->project_name)

@section('content')
    <section class="container-dx py-20 sm:py-28">
        <div class="mx-auto max-w-2xl text-center">
            <span class="mx-auto grid size-16 place-items-center rounded-full bg-lime text-ink" aria-hidden="true"><x-icon name="check" :size="30" /></span>
            <h1 class="mt-8 font-display text-4xl font-semibold tracking-tight sm:text-5xl">Thank you{{ $questionnaire->contactName() ? ', '.\Illuminate\Support\Str::before($questionnaire->contactName(), ' ') : '' }}</h1>
            <p class="mt-5 text-lg leading-relaxed text-muted">
                We have received your answers for <strong class="text-text">{{ $questionnaire->project_name }}</strong>
                @if ($questionnaire->submitted_at)
                    on {{ $questionnaire->submitted_at->format('j F Y') }}
                @endif.
                Our team will review everything and get back to you to discuss the next steps.
            </p>
            <p class="mt-6 text-sm text-subtle">Need to change something? Just reply to our email or contact us, and we will reopen the questionnaire for you.</p>
            <a href="{{ route('home') }}" class="btn btn-secondary mt-10">Visit our website</a>
        </div>
    </section>
@endsection
