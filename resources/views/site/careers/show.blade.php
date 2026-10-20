@extends('layouts.site')

@section('content')
    @include('site.partials.preview-banner', ['status' => $job->status->label()])

    <article>
        <header class="surface-dark relative overflow-hidden">
            <div class="grid-lines pointer-events-none absolute inset-0 [mask-image:linear-gradient(to_bottom,black,transparent)]" aria-hidden="true"></div>
            <div class="container-dx relative pt-12 pb-14 sm:pt-16 sm:pb-20">
                <x-site.breadcrumbs :items="$seo->breadcrumbItems()" />
                <div class="mt-10 max-w-4xl">
                    <p class="eyebrow accent">{{ $job->department?->name ?? 'Careers' }}</p>
                    <h1 class="display-lg mt-5">{{ $job->title }}</h1>
                    <p class="lede text-muted mt-6">{{ $job->summary }}</p>
                </div>
                <dl class="mt-10 flex flex-wrap gap-2 text-sm">
                    <div class="chip"><dt class="sr-only">Employment type</dt><dd>{{ $job->employmentTypeLabel() }}</dd></div>
                    <div class="chip"><dt class="sr-only">Work arrangement</dt><dd>{{ $job->work_arrangement->label() }}</dd></div>
                    <div class="chip"><dt class="sr-only">Location</dt><dd class="flex items-center gap-1.5"><x-icon name="map-pin" :size="14" /> {{ $job->location }}</dd></div>
                    @if ($job->salary_range)
                        <div class="chip"><dt class="sr-only">Salary</dt><dd>{{ $job->salary_range }}</dd></div>
                    @endif
                    @if ($job->application_deadline)
                        <div class="chip"><dt>{{ $job->isAcceptingApplications() ? 'Apply by' : 'Closed' }}</dt><dd>{{ $job->application_deadline->format('j F Y, H:i') }}</dd></div>
                    @endif
                </dl>
            </div>
        </header>

        <div class="surface-light">
            <div class="container-dx grid gap-14 py-16 sm:py-20 lg:grid-cols-12">
                <div class="space-y-14 lg:col-span-7">
                    @foreach ($contentSections as $key => $block)
                        <section aria-labelledby="job-{{ $key }}">
                            <h2 id="job-{{ $key }}" class="heading-lg">{{ $block['label'] }}</h2>
                            <div class="prose-dx mt-5">{!! $block['html'] !!}</div>
                        </section>
                    @endforeach
                </div>

                <aside class="lg:col-span-4 lg:col-start-9">
                    <div class="card p-7 lg:sticky lg:top-28">
                        @if (! $job->isAcceptingApplications() && ! $isPreview)
                            <h2 class="heading-md">Applications closed</h2>
                            <p class="mt-3 text-paper-muted">This role is no longer accepting applications. See our other <a href="{{ route('careers.index') }}" class="link-underline font-semibold text-ink-text">open roles</a>.</p>
                        @elseif ($job->application_method === \App\Enums\ApplicationMethod::Email)
                            <h2 class="heading-md">How to apply</h2>
                            @if ($job->application_instructions)
                                <p class="mt-3 text-paper-muted">{{ $job->application_instructions }}</p>
                            @endif
                            <a href="mailto:{{ $job->application_email }}?subject={{ rawurlencode('Application: '.$job->title) }}" class="btn btn-dark mt-6 w-full"><x-icon name="mail" :size="18" /> Email your application</a>
                        @elseif ($job->application_method === \App\Enums\ApplicationMethod::External)
                            <h2 class="heading-md">How to apply</h2>
                            @if ($job->application_instructions)
                                <p class="mt-3 text-paper-muted">{{ $job->application_instructions }}</p>
                            @endif
                            <a href="{{ $job->application_url }}" class="btn btn-dark mt-6 w-full" target="_blank" rel="noopener">Apply on the application site <x-icon name="arrow-up-right" :size="16" /></a>
                        @else
                            <h2 class="heading-md">Apply for this role</h2>
                            <p class="mt-3 text-paper-muted">{{ $job->application_instructions ?: 'Complete the form below. It takes about ten minutes, and you will receive an email confirming we have your application.' }}</p>
                            <a href="#apply" class="btn btn-dark mt-6 w-full">Start your application <x-icon name="arrow-right" :size="16" class="btn-arrow" /></a>
                        @endif
                    </div>
                </aside>
            </div>
        </div>
    </article>

    @if ($job->acceptsFormApplications() && ! $isPreview)
        <section id="apply" class="surface-dark section" aria-labelledby="apply-heading">
            <div class="container-dx grid gap-12 lg:grid-cols-12">
                <div class="lg:col-span-4">
                    <p class="eyebrow accent">Application</p>
                    <h2 id="apply-heading" class="display-md mt-5">Apply for {{ $job->title }}</h2>
                    <p class="mt-6 flex items-start gap-3 text-sm text-muted">
                        <x-icon name="lock" :size="18" class="mt-0.5 flex-none text-lime" />
                        Your CV is stored privately and is only accessible to the people reviewing applications for this role.
                    </p>
                </div>
                <div class="card p-6 sm:p-10 lg:col-span-8">
                    <form method="POST" action="{{ route('careers.apply', $job) }}" enctype="multipart/form-data" class="grid gap-6" data-submit-form novalidate>
                        @csrf
                        <x-site.spam-fields id="application" />
                        <x-site.form-errors />

                        <div class="grid gap-6 sm:grid-cols-2">
                            <x-site.input name="name" label="Full name" required autocomplete="name" maxlength="120" />
                            <x-site.input name="email" type="email" label="Email address" required autocomplete="email" maxlength="190" />
                            <x-site.input name="phone" type="tel" label="Phone" autocomplete="tel" maxlength="40" />
                            <x-site.input name="portfolio_url" type="url" label="Portfolio or professional profile" placeholder="https://" maxlength="500" hint="For example LinkedIn, GitHub, Behance or a personal site." />
                        </div>

                        <x-site.textarea name="cover_letter" label="Why this role?" required :rows="8" maxlength="6000" hint="Tell us what interests you about the role and the experience you would bring. Plain text is fine." />

                        <div>
                            <span class="field-label" id="cv-label">CV / résumé <span class="accent" aria-hidden="true">*</span></span>
                            <label for="f-cv" class="flex cursor-pointer flex-wrap items-center gap-4 rounded-[var(--radius-field)] border border-dashed border-field bg-ink-2 p-5 transition hover:border-muted focus-within:border-lime">
                                <x-icon name="upload" :size="22" class="text-lime" />
                                <span class="flex-1">
                                    <span class="block font-semibold">Choose a file</span>
                                    <span class="block text-sm text-subtle">PDF, DOC or DOCX · up to {{ (int) round(config('dxcovr.careers.cv_max_kb') / 1024) }} MB</span>
                                    <span id="cv-file-name" class="mt-1 block text-sm text-lime" aria-live="polite"></span>
                                </span>
                                <input id="f-cv" name="cv" type="file" required accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" class="sr-only" data-file-input="cv-file-name" aria-labelledby="cv-label" @error('cv') aria-invalid="true" aria-describedby="f-cv-error" @enderror>
                            </label>
                            @error('cv')
                                <p id="f-cv-error" class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <x-site.consent :notice="$privacyNotice" id="application-consent" />

                        <div class="flex flex-wrap items-center gap-4">
                            <button type="submit" class="btn btn-primary" data-loading-text="Uploading…">
                                <span data-label>Submit application</span>
                                <x-icon name="arrow-right" :size="18" class="btn-arrow" />
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    @endif
@endsection
