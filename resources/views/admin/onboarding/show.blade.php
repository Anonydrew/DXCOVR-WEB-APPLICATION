@extends('layouts.admin')

@section('title', 'Onboarding · '.$questionnaire->project_name)

@php
    $siteName = app(\App\Services\SiteSettings::class)->get('site.name');
    $link = $questionnaire->shareUrl();
    $firstName = \Illuminate\Support\Str::before($questionnaire->client_name, ' ');
    $shareText = "Hi {$firstName},\n\nTo help us plan {$questionnaire->project_name}, please fill in this short questionnaire about your goals, style and inspirations. It takes about 15 minutes and saves as you go:\n\n{$link}\n\nThank you,\n{$siteName}";
    $progress = $questionnaire->progress();
    $hasAnswers = filled($questionnaire->answers) || $questionnaire->files->isNotEmpty();
@endphp

@section('content')
    <x-admin.page-header :title="$questionnaire->project_name" :description="collect([$questionnaire->client_name, $questionnaire->client_company])->filter()->implode(' · ')" :back="route('admin.onboarding.index')">
        <x-slot:actions>
            @can('onboarding.manage')
                <a href="{{ route('admin.onboarding.edit', $questionnaire) }}" class="a-btn"><x-icon name="edit" :size="16" /> Edit</a>
                <x-admin.delete-button :action="route('admin.onboarding.destroy', $questionnaire)" confirm="Delete this questionnaire, its answers and all uploaded files permanently?" />
            @endcan
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="min-w-0 space-y-6">
            @can('onboarding.manage')
                <section class="a-card" aria-labelledby="share-heading">
                    <div class="a-card-header">
                        <h2 id="share-heading" class="a-card-title">Share with your client</h2>
                        @if ($questionnaire->isExpired())
                            <span class="a-badge a-badge-danger">Link expired</span>
                        @elseif ($questionnaire->expires_at)
                            <span class="text-xs a-subtle">Expires {{ $questionnaire->expires_at->format('j M Y') }}</span>
                        @endif
                    </div>
                    <div class="a-card-body space-y-4">
                        <div class="flex flex-col gap-2 sm:flex-row">
                            <label for="share-link" class="sr-only">Questionnaire link</label>
                            <input id="share-link" type="text" readonly value="{{ $link }}" class="a-input font-mono text-xs" @focus="$el.select()">
                            <button type="button" class="a-btn a-btn-primary flex-none" data-copy="{{ $link }}"><x-icon name="copy" :size="16" /> Copy link</button>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a href="mailto:{{ $questionnaire->client_email }}?subject={{ rawurlencode('Your project questionnaire: '.$questionnaire->project_name) }}&body={{ rawurlencode($shareText) }}" class="a-btn a-btn-sm"><x-icon name="mail" :size="15" /> Email link</a>
                            <a href="https://wa.me/?text={{ rawurlencode($shareText) }}" target="_blank" rel="noopener noreferrer" class="a-btn a-btn-sm"><x-icon name="whatsapp" :size="15" /> Share on WhatsApp</a>
                            <a href="{{ $link }}" target="_blank" rel="noopener noreferrer" class="a-btn a-btn-sm a-btn-ghost"><x-icon name="eye" :size="15" /> Preview as client</a>
                        </div>
                        <p class="text-xs a-subtle">Anyone with this link can view and edit the answers, so share it only with your client. If it is sent to the wrong person, generate a new link.</p>
                        <div class="flex flex-wrap gap-2 border-t border-line pt-4">
                            @if ($questionnaire->isSubmitted())
                                <form method="POST" action="{{ route('admin.onboarding.reopen', $questionnaire) }}">
                                    @csrf
                                    <button type="submit" class="a-btn a-btn-sm"><x-icon name="history" :size="15" /> Reopen for client edits</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('admin.onboarding.regenerate', $questionnaire) }}" data-confirm="Generate a new link? The current link will stop working immediately.">
                                @csrf
                                <button type="submit" class="a-btn a-btn-sm a-btn-ghost"><x-icon name="lock" :size="15" /> Generate a new link</button>
                            </form>
                        </div>
                    </div>
                </section>
            @endcan

            @if (! $hasAnswers)
                <section class="a-card a-card-body">
                    <x-admin.empty-state title="Waiting for the client" icon="clock">
                        {{ $questionnaire->first_opened_at ? 'The client opened the questionnaire on '.$questionnaire->first_opened_at->format('j M Y').' but has not saved any answers yet.' : 'The client has not opened the questionnaire yet.' }}
                    </x-admin.empty-state>
                </section>
            @else
                @foreach ($sections as $sectionKey => $section)
                    @php
                        $answers = $questionnaire->answers[$sectionKey] ?? [];
                        $answered = \App\Support\Onboarding\QuestionnaireForm::sectionAnswered($sectionKey, $questionnaire->answers ?? [], $questionnaire->files->count());
                    @endphp
                    <section class="a-card" aria-labelledby="section-{{ $sectionKey }}">
                        <div class="a-card-header {{ $answered || $answers ? '' : 'border-b-0' }}">
                            <h2 id="section-{{ $sectionKey }}" class="a-card-title">{{ $section['title'] }}</h2>
                            @unless ($answered)
                                <span class="text-xs a-subtle">Not answered</span>
                            @endunless
                        </div>
                        @if ($answered || $answers)
                        <dl class="a-card-body space-y-5">
                            @foreach ($section['fields'] as $fieldKey => $field)
                                @if ($field['type'] === 'uploads')
                                    <div>
                                        <dt class="a-mono a-subtle">{{ $field['label'] }}</dt>
                                        <dd class="mt-2">
                                            @if ($questionnaire->files->isEmpty())
                                                <span class="text-sm a-subtle">No files uploaded.</span>
                                            @else
                                                <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                                    @foreach ($questionnaire->files as $file)
                                                        @php $fileUrl = route('admin.onboarding.files.show', [$questionnaire, $file->id]); @endphp
                                                        <li class="overflow-hidden rounded-xl border border-line bg-ink-2">
                                                            @if ($file->isImage())
                                                                <a href="{{ $fileUrl }}" target="_blank" rel="noopener" class="block aspect-[4/3] bg-surface-2">
                                                                    <img src="{{ $fileUrl }}" alt="{{ $file->original_name }}" class="size-full object-cover" loading="lazy">
                                                                </a>
                                                            @else
                                                                <a href="{{ $fileUrl }}?download=1" class="grid aspect-[4/3] place-items-center bg-surface-2 a-muted"><x-icon name="file" :size="32" /></a>
                                                            @endif
                                                            <div class="flex items-center gap-2 p-3">
                                                                <span class="min-w-0 flex-1">
                                                                    <span class="block truncate text-sm">{{ $file->original_name }}</span>
                                                                    <span class="block text-xs a-subtle">{{ $file->humanSize() }}</span>
                                                                </span>
                                                                <a href="{{ $fileUrl }}?download=1" class="a-icon-btn" aria-label="Download {{ $file->original_name }}"><x-icon name="download" :size="16" /></a>
                                                            </div>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </dd>
                                    </div>
                                @elseif (array_key_exists($fieldKey, $answers))
                                    <div>
                                        <dt class="a-mono a-subtle">{{ $field['label'] }}</dt>
                                        <dd class="mt-2">@include('admin.onboarding.partials.answer', ['field' => $field, 'value' => $answers[$fieldKey]])</dd>
                                    </div>
                                @endif
                            @endforeach
                        </dl>
                        @endif
                    </section>
                @endforeach
            @endif
        </div>

        <aside class="space-y-6">
            <section class="a-card a-card-body space-y-4" aria-labelledby="status-heading">
                <div class="flex items-center justify-between gap-3">
                    <h2 id="status-heading" class="a-card-title">Status</h2>
                    <x-admin.status-badge :status="$questionnaire->status" />
                </div>
                <div>
                    <div class="h-1.5 overflow-hidden rounded-full bg-surface-3" aria-hidden="true"><div class="h-full rounded-full bg-lime" style="width: {{ $progress['total'] ? round($progress['answered'] / $progress['total'] * 100) : 0 }}%"></div></div>
                    <p class="mt-2 text-sm a-muted">{{ $progress['answered'] }} of {{ $progress['total'] }} sections answered</p>
                </div>
                <dl class="space-y-2.5 text-sm">
                    @foreach ([
                        'Created' => $questionnaire->created_at,
                        'First opened' => $questionnaire->first_opened_at,
                        'Last saved' => $questionnaire->last_saved_at,
                        'Submitted' => $questionnaire->submitted_at,
                    ] as $label => $date)
                        <div class="flex justify-between gap-4">
                            <dt class="a-subtle">{{ $label }}</dt>
                            <dd class="text-right">{{ $date ? $date->format('j M Y, H:i') : 'Not set' }}</dd>
                        </div>
                    @endforeach
                    @if ($questionnaire->creator)
                        <div class="flex justify-between gap-4"><dt class="a-subtle">Created by</dt><dd>{{ $questionnaire->creator->name }}</dd></div>
                    @endif
                    @if ($questionnaire->client_email)
                        <div class="flex justify-between gap-4"><dt class="a-subtle">Client email</dt><dd class="min-w-0 truncate"><a href="mailto:{{ $questionnaire->client_email }}" class="hover:text-lime">{{ $questionnaire->client_email }}</a></dd></div>
                    @endif
                    @if ($questionnaire->enquiry)
                        <div class="flex justify-between gap-4"><dt class="a-subtle">From enquiry</dt><dd>
                            @can('enquiries.view')
                                <a href="{{ route('admin.enquiries.show', $questionnaire->enquiry) }}" class="hover:text-lime">{{ $questionnaire->enquiry->name }}</a>
                            @else
                                {{ $questionnaire->enquiry->name }}
                            @endcan
                        </dd></div>
                    @endif
                </dl>
            </section>

            @can('onboarding.manage')
                <form method="POST" action="{{ route('admin.onboarding.notes', $questionnaire) }}" class="a-card a-card-body space-y-4">
                    @csrf
                    @method('PATCH')
                    <h2 class="a-card-title">Internal notes</h2>
                    <x-admin.textarea name="internal_notes" label="Notes" :value="$questionnaire->internal_notes" rows="6" hint="Only visible to your team, never to the client." />
                    <button type="submit" class="a-btn a-btn-primary">Save notes</button>
                </form>
            @elseif ($questionnaire->internal_notes)
                <section class="a-card a-card-body">
                    <h2 class="a-card-title">Internal notes</h2>
                    <p class="mt-3 whitespace-pre-line text-sm">{{ $questionnaire->internal_notes }}</p>
                </section>
            @endcan
        </aside>
    </div>
@endsection
