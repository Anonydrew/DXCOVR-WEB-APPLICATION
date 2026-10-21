@extends('layouts.admin')

@section('title', $section->exists ? 'Edit section' : 'Add section')

@section('content')
    <x-admin.page-header :title="$section->exists ? 'Edit section' : 'Add section'" description="A section is one step of the questionnaire." :back="route('admin.onboarding.questions.index')">
        <x-slot:actions>
            @if ($section->exists && ! $section->isSystem())
                <x-admin.delete-button :action="route('admin.onboarding.sections.destroy', $section)"
                    :confirm="'Delete this section'.($section->questions_count ? ' and its '.$section->questions_count.' '.\Illuminate\Support\Str::plural('question', $section->questions_count) : '').'? Questionnaires already created are not affected.'" />
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <form method="POST" action="{{ $section->exists ? route('admin.onboarding.sections.update', $section) : route('admin.onboarding.sections.store') }}" class="max-w-3xl space-y-6">
        @csrf
        @if ($section->exists) @method('PUT') @endif

        <div class="a-card a-card-body space-y-5">
            <x-admin.input name="title" label="Title" :value="$section->title" required maxlength="120" hint="Shown as the step name, e.g. “Your project”." />
            <x-admin.textarea name="intro" label="Introduction" :value="$section->intro" rows="3" maxlength="1000" hint="One or two sentences under the title explaining what this step is about. Optional." />

            @if ($section->isSystem())
                <input type="hidden" name="always_included" value="1">
                <p class="flex items-start gap-2 text-sm a-muted"><x-icon name="lock" :size="16" class="mt-0.5 flex-none" /> Every questionnaire includes this section because it holds built-in questions.</p>
            @else
                <x-admin.toggle name="always_included" label="Always include this section" :checked="$section->always_included" hint="When off, you can untick this section for a client when you create their questionnaire." />
            @endif
        </div>

        @include('admin.partials.save-bar', ['label' => $section->exists ? 'Save section' : 'Add section'])
    </form>
@endsection
