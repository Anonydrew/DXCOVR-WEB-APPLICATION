@extends('layouts.admin')

@section('title', $question->exists ? 'Edit question' : 'Add question')

@php
    $form = \App\Support\Onboarding\QuestionnaireForm::class;
    $type = old('type', $question->type);
    $typeHints = [
        'text' => 'A single line of text.',
        'textarea' => 'A larger box for a few sentences or more.',
        'email' => 'An email address, checked for the right format.',
        'tel' => 'A phone or WhatsApp number.',
        'url' => 'A web address starting with http:// or https://.',
        'date' => 'A date picked from a calendar.',
        'radio' => 'Clients pick one of the options you write below.',
        'checkboxes' => 'Clients tick any of the options you write below.',
        'styles' => 'Clients pick from '.count($form::STYLES).' illustrated style cards (minimal, bold, dark, warm and more).',
        'palettes' => 'Clients pick from '.count($form::PALETTES).' ready-made colour palettes.',
        'typography' => 'Clients pick one of '.count($form::TYPOGRAPHY).' lettering styles shown as samples.',
        'references' => 'Rows where clients paste links to websites or apps they like, with a note on each.',
        'uploads' => 'Clients upload images or PDFs (up to '.config('dxcovr.onboarding.max_files').' files in total). Only one upload question is allowed.',
        'consent' => 'A checkbox the client must tick to submit. Write the statement they confirm as the question.',
    ];
    $optionRows = collect($question->options ?? [])->map(fn (array $option) => ['label' => $option['label'], 'value' => $option['value']])->all();
@endphp

@section('content')
    <x-admin.page-header :title="$question->exists ? 'Edit question' : 'Add question'" :back="route('admin.onboarding.questions.index')">
        <x-slot:actions>
            @if ($question->exists && ! $locked)
                <x-admin.delete-button :action="route('admin.onboarding.questions.destroy', $question)" confirm="Delete this question? Questionnaires already created are not affected." />
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <form method="POST" action="{{ $question->exists ? route('admin.onboarding.questions.update', $question) : route('admin.onboarding.questions.store') }}" class="max-w-3xl space-y-6"
          x-data="{ type: @js($type), hints: @js($typeHints) }">
        @csrf
        @if ($question->exists) @method('PUT') @endif

        @if ($locked)
            <div class="a-alert a-alert-info">
                <x-icon name="lock" :size="18" class="mt-0.5 flex-none" />
                <p>This built-in question provides {{ $question->type === 'consent' ? 'the client’s consent' : ($question->type === 'email' ? 'the email address replies go to' : 'the name used to greet the client') }}. You can reword it, but not delete it, move it or change its type.</p>
            </div>
        @endif

        <div class="a-card a-card-body space-y-5">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-admin.select name="questionnaire_section_id" label="Section" :options="$sections" :value="$question->questionnaire_section_id" required :disabled="$locked" />

                <div>
                    <label for="f-type" class="a-label">Answer type <span class="text-lime" aria-hidden="true">*</span></label>
                    <select id="f-type" name="type" class="a-input" x-model="type" required @disabled($locked) aria-describedby="f-type-hint">
                        @foreach ($form::TYPES as $value => $label)
                            <option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p id="f-type-hint" class="a-hint" x-text="hints[type]">{{ $typeHints[$type] ?? '' }}</p>
                    @error('type')
                        <p class="a-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <x-admin.textarea name="label" label="Question" :value="$question->label" required rows="2" maxlength="500" />
                <p class="a-hint" x-show="type === 'consent'" x-cloak>For a confirmation checkbox, write the full statement, e.g. “I confirm DXCOVR may use this information to plan my project.”</p>
            </div>

            <x-admin.input name="hint" label="Help text" :value="$question->hint" maxlength="500" hint="Shown under the question, e.g. “For example: bookings per week, time saved.” Optional." />

            <div x-show="! ['uploads', 'consent'].includes(type)">
                <x-admin.toggle name="is_required" label="Required to submit" :checked="$question->is_required" hint="Clients can save without answering, but cannot submit until they do." />
            </div>
        </div>

        <div class="a-card a-card-body space-y-4" x-show="['radio', 'checkboxes'].includes(type)" x-cloak>
            <x-admin.repeater name="options" label="Options" :rows="$optionRows" :max="40" add-label="Add option"
                :fields="['label' => ['label' => 'Option', 'max' => 200], 'value' => ['type' => 'hidden']]"
                hint="Rewording an option keeps earlier answers matched to it. At least two options are needed." />
        </div>

        <div class="a-card a-card-body grid gap-5 sm:grid-cols-2" x-show="['text', 'textarea', 'tel', 'checkboxes', 'styles', 'palettes', 'references'].includes(type)" x-cloak>
            <div x-show="['text', 'textarea', 'tel'].includes(type)">
                <x-admin.input name="max_length" type="number" label="Character limit" :value="$question->max_length" min="1" max="10000" hint="Leave empty for the default: 200 for short answers, 3,000 for paragraphs, 40 for phone numbers." />
            </div>
            <div x-show="['checkboxes', 'styles', 'palettes'].includes(type)">
                <x-admin.input name="max_choices" type="number" label="Maximum choices" :value="$question->max_choices" min="1" max="40" hint="Leave empty to allow any number." x-bind:disabled="type === 'references'" />
            </div>
            <div x-show="type === 'references'">
                <x-admin.input name="max_choices" id="f-max-links" type="number" label="Number of link rows" :value="$question->max_choices" min="1" max="20" hint="Leave empty for 8." x-bind:disabled="type !== 'references'" />
            </div>
        </div>

        @include('admin.partials.save-bar', ['label' => $question->exists ? 'Save question' : 'Add question', 'note' => 'Changes apply to questionnaires you create from now on.'])
    </form>
@endsection
