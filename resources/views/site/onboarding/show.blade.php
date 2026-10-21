@extends('layouts.client')

@section('title', 'Onboarding · '.$questionnaire->project_name)

@php
    $preview ??= false;
    $keys = array_keys($sections);
    $total = count($keys);
    $errorCount = count($errors->getBag('default')->keys());
@endphp

@section('content')
    <section class="relative overflow-hidden border-b border-line">
        <div class="container-dx relative py-12 sm:py-16">
            <p class="eyebrow accent">Welcome{{ $questionnaire->contactName() ? ', '.\Illuminate\Support\Str::before($questionnaire->contactName(), ' ') : '' }}</p>
            <h1 class="mt-4 max-w-3xl font-display text-4xl font-semibold leading-tight tracking-tight sm:text-5xl">Let’s shape {{ $questionnaire->project_name }}</h1>
            <div class="mt-5 max-w-2xl text-lg leading-relaxed text-muted">
                @if ($questionnaire->intro_message)
                    <p class="whitespace-pre-line">{{ $questionnaire->intro_message }}</p>
                @else
                    <p>This questionnaire helps us understand your goals, your audience and your taste before we start. Pick the styles, colours and examples that feel right. There are no wrong answers.</p>
                @endif
            </div>
            <ul class="mt-7 flex flex-wrap gap-x-6 gap-y-2 text-sm text-subtle">
                <li class="flex items-center gap-2"><x-icon name="layers" :size="16" /> {{ $total }} short sections</li>
                <li class="flex items-center gap-2"><x-icon name="clock" :size="16" /> About 15 minutes</li>
                <li class="flex items-center gap-2"><x-icon name="check" :size="16" /> Saves as you go, so you can return with this link</li>
            </ul>
        </div>
    </section>

    <div class="container-dx py-10 sm:py-14">
        <div class="grid grid-cols-[minmax(0,1fr)] gap-10 lg:grid-cols-[15rem_minmax(0,1fr)]">
            <aside class="lg:sticky lg:top-8 lg:self-start" aria-label="Questionnaire sections">
                <div class="ob-progress" aria-hidden="true"><span data-progress-bar style="width: {{ round((array_search($step, $keys) + 1) / $total * 100) }}%"></span></div>
                <p class="mt-3 text-sm text-subtle" data-progress-label>Step {{ array_search($step, $keys) + 1 }} of {{ $total }}</p>
                <ol class="ob-steps mt-5">
                    @foreach ($sections as $key => $section)
                        @php $answered = \App\Support\Onboarding\QuestionnaireForm::sectionAnswered($key, $section, $questionnaire->answers ?? [], $questionnaire->files->count()); @endphp
                        <li>
                            <a href="{{ $preview ? request()->fullUrlWithQuery(['step' => $key]) : route('onboarding.show', ['token' => $token, 'step' => $key]) }}" class="ob-step-link {{ $key === $step ? 'is-current' : '' }} {{ $answered ? 'is-done' : '' }}" data-step-link="{{ $key }}" @if ($key === $step) aria-current="step" @endif>
                                <span class="ob-step-number" aria-hidden="true"><span class="ob-step-index">{{ $loop->iteration }}</span><x-icon name="check" :size="13" class="ob-step-check" /></span>
                                {{ $section['title'] }}
                            </a>
                        </li>
                    @endforeach
                </ol>
            </aside>

            <form id="questionnaire" method="POST" action="{{ $preview ? '' : route('onboarding.update', $token) }}" enctype="multipart/form-data" novalidate
                  data-questionnaire data-current="{{ $step }}" data-upload-url="{{ $preview ? '' : route('onboarding.files.store', $token) }}" data-max-files="{{ config('dxcovr.onboarding.max_files') }}" @if ($preview) data-preview @endif>
                @csrf
                <input type="hidden" name="step" value="{{ $step }}" data-step-input>

                @if ($preview)
                    <div class="ob-notice mb-6" role="status"><x-icon name="eye" :size="18" /> Preview of the current questions. Nothing you enter here is saved.</div>
                @endif

                @if (session('status'))
                    <div class="ob-notice mb-6" role="status"><x-icon name="check" :size="18" /> {{ session('status') }}</div>
                @endif
                @if ($errorCount)
                    <div class="ob-notice ob-notice-error mb-6" role="alert" tabindex="-1" data-error-summary>
                        <x-icon name="help" :size="18" /> Please check {{ $errorCount === 1 ? 'the highlighted answer' : 'the '.$errorCount.' highlighted answers' }}.
                    </div>
                @endif

                <p class="mb-6 text-sm text-subtle">Answer as much or as little as you like. Only questions marked <span class="accent">*</span> are needed before you submit.</p>

                @foreach ($sections as $sectionKey => $section)
                    <fieldset class="ob-step {{ $sectionKey === $step ? 'is-current' : '' }}" id="step-{{ $sectionKey }}" data-step="{{ $sectionKey }}">
                        <legend class="sr-only">{{ $section['title'] }}</legend>
                        <div class="mb-8">
                            <p class="mono-label text-subtle">Step {{ $loop->iteration }} of {{ $total }}</p>
                            <h2 class="mt-2 font-display text-3xl font-semibold tracking-tight" tabindex="-1" data-step-heading>{{ $section['title'] }}</h2>
                            @if (filled($section['intro'] ?? null))
                                <p class="mt-2 text-muted">{{ $section['intro'] }}</p>
                            @endif
                        </div>

                        <div class="space-y-9">
                            @foreach ($section['fields'] as $fieldKey => $field)
                                @include('site.onboarding.partials.field', [
                                    'sectionKey' => $sectionKey,
                                    'fieldKey' => $fieldKey,
                                    'field' => $field,
                                    'value' => $questionnaire->answer($sectionKey.'.'.$fieldKey),
                                    'required' => ! empty($field['required']),
                                ])
                            @endforeach
                        </div>

                        <div class="ob-step-actions">
                            @unless ($loop->first)
                                <button type="button" class="btn btn-secondary js-only" data-step-prev><x-icon name="arrow-left" :size="18" /> Back</button>
                            @endunless
                            @if ($loop->last)
                                <button type="submit" name="action" value="submit" class="btn btn-primary ml-auto">Submit questionnaire <x-icon name="arrow-right" :size="18" class="btn-arrow" /></button>
                            @else
                                <button type="button" class="btn btn-primary js-only ml-auto" data-step-next>Next: {{ $sections[$keys[$loop->index + 1]]['title'] }} <x-icon name="arrow-right" :size="18" class="btn-arrow" /></button>
                            @endif
                        </div>
                    </fieldset>
                @endforeach

                <div class="mt-8 flex flex-wrap items-center gap-4 border-t border-line pt-6">
                    <button type="submit" name="action" value="save" class="btn btn-secondary">Save and continue later</button>
                    <p class="text-sm text-subtle" data-save-status aria-live="polite">
                        @if ($questionnaire->last_saved_at)
                            Last saved {{ $questionnaire->last_saved_at->diffForHumans() }}.
                        @endif
                    </p>
                </div>
            </form>
        </div>
    </div>
@endsection
