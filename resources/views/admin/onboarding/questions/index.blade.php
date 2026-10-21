@extends('layouts.admin')

@section('title', 'Questionnaire builder')

@section('content')
    <x-admin.page-header title="Questionnaire builder" description="The sections and questions your clients see in new onboarding questionnaires. Drag to reorder. Questionnaires you have already created keep their questions until you update them." :back="route('admin.onboarding.index')">
        <x-slot:actions>
            <a href="{{ route('admin.onboarding.questions.preview') }}" target="_blank" rel="noopener" class="a-btn"><x-icon name="eye" :size="16" /> Preview</a>
            <a href="{{ route('admin.onboarding.sections.create') }}" class="a-btn a-btn-primary"><x-icon name="plus" :size="16" /> Add section</a>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($sections->isEmpty())
        <x-admin.empty-state title="No sections yet" icon="layers">Add a section, then the questions your clients should answer in it.</x-admin.empty-state>
    @else
        <ol class="space-y-6" x-data="sortableList(@js(route('admin.reorder', 'questionnaire-sections')), { handle: '.js-section-handle', attribute: 'data-section-id' })">
            @foreach ($sections as $section)
                <li class="a-card scroll-mt-24" id="section-{{ $section->id }}" data-section-id="{{ $section->id }}" aria-labelledby="section-title-{{ $section->id }}">
                    <div class="a-card-header gap-3">
                        <span class="a-drag-handle js-section-handle a-icon-btn" title="Drag to reorder sections" aria-hidden="true"><x-icon name="drag" :size="16" /></span>
                        <div class="min-w-0 flex-1">
                            <h2 id="section-title-{{ $section->id }}" class="a-card-title">
                                <span class="a-subtle font-normal">{{ $loop->iteration }}.</span> {{ $section->title }}
                            </h2>
                            @if ($section->intro)
                                <p class="mt-0.5 truncate text-xs a-subtle">{{ $section->intro }}</p>
                            @endif
                        </div>
                        <span class="a-badge {{ $section->always_included ? 'a-badge-success' : 'a-badge-muted' }}" title="{{ $section->always_included ? 'Every questionnaire includes this section.' : 'Can be unticked for a client when you create their questionnaire.' }}">{{ $section->always_included ? 'Always included' : 'Optional' }}</span>
                        <a href="{{ route('admin.onboarding.sections.edit', $section) }}" class="a-icon-btn" aria-label="Edit section {{ $section->title }}"><x-icon name="edit" :size="16" /></a>
                    </div>

                    @if ($section->questions->isEmpty())
                        <p class="px-4 py-4 text-sm a-subtle">No questions yet. Sections without questions are skipped.</p>
                    @else
                        <ul class="divide-y divide-line" x-data="sortableList(@js(route('admin.reorder', 'questionnaire-questions')))" aria-label="Questions in {{ $section->title }}">
                            @foreach ($section->questions as $question)
                                @php $question->setRelation('section', $section); @endphp
                                <li class="flex items-center gap-4 px-4 py-3" data-id="{{ $question->id }}">
                                    <span class="a-drag-handle a-icon-btn" title="Drag to reorder questions" aria-hidden="true"><x-icon name="drag" :size="16" /></span>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-medium">
                                            {{ \Illuminate\Support\Str::limit($question->label, 140) }}
                                            @if ($question->type === 'consent' || ($question->is_required && $question->type !== 'uploads'))
                                                <span class="text-lime" title="Required to submit">*</span>
                                            @endif
                                        </p>
                                        <p class="truncate text-xs a-subtle">
                                            {{ $question->typeLabel() }}
                                            @if (in_array($question->type, \App\Support\Onboarding\QuestionnaireForm::CHOICE_TYPES, true))
                                                · {{ count($question->options ?? []) }} options
                                            @endif
                                            @if ($question->max_choices && in_array($question->type, \App\Support\Onboarding\QuestionnaireForm::MULTI_TYPES, true))
                                                · up to {{ $question->max_choices }}
                                            @endif
                                            @if ($question->hint)
                                                · {{ $question->hint }}
                                            @endif
                                        </p>
                                    </div>
                                    @if ($question->isSystem())
                                        <span class="a-badge a-badge-muted" title="Used by the website, so it can be reworded but not deleted, moved or retyped."><x-icon name="lock" :size="12" /> Built in</span>
                                    @endif
                                    <a href="{{ route('admin.onboarding.questions.edit', $question) }}" class="a-icon-btn" aria-label="Edit question {{ \Illuminate\Support\Str::limit($question->label, 60) }}"><x-icon name="edit" :size="16" /></a>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <div class="border-t border-line px-4 py-3">
                        <a href="{{ route('admin.onboarding.questions.create', ['section' => $section->id]) }}" class="a-btn a-btn-sm"><x-icon name="plus" :size="14" /> Add question</a>
                    </div>
                </li>
            @endforeach
        </ol>

        <p class="mt-6 text-xs a-subtle"><span class="text-lime">*</span> Required before the client can submit. Built-in questions provide the client’s name, the reply-to email address and their consent, so they cannot be removed.</p>
    @endif
@endsection
