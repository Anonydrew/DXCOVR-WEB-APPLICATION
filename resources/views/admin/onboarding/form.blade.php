@extends('layouts.admin')

@section('title', $questionnaire->exists ? 'Edit questionnaire' : 'New questionnaire')

@section('content')
    <x-admin.page-header
        :title="$questionnaire->exists ? 'Edit questionnaire' : 'New client questionnaire'"
        description="Your client receives a private link. They can save and come back, and you are notified when they submit."
        :back="$questionnaire->exists ? route('admin.onboarding.show', $questionnaire) : route('admin.onboarding.index')" />

    <form method="POST" action="{{ $questionnaire->exists ? route('admin.onboarding.update', $questionnaire) : route('admin.onboarding.store') }}" class="max-w-3xl space-y-6">
        @csrf
        @if ($questionnaire->exists) @method('PUT') @endif
        @if ($questionnaire->enquiry_id)
            <input type="hidden" name="enquiry_id" value="{{ $questionnaire->enquiry_id }}">
        @endif

        <section class="a-card a-card-body grid gap-5 sm:grid-cols-2" aria-label="Client">
            <x-admin.input name="client_name" label="Client name" :value="$questionnaire->client_name" required maxlength="160" />
            <x-admin.input name="client_company" label="Organisation" :value="$questionnaire->client_company" maxlength="160" />
            <x-admin.input name="client_email" type="email" label="Client email" :value="$questionnaire->client_email" hint="Used to prepare the share email. Nothing is sent automatically." />
            <x-admin.input name="project_name" label="Project name" :value="$questionnaire->project_name" required maxlength="160" hint="Shown to the client, e.g. “New clinic website”." />
            <x-admin.textarea name="intro_message" label="Personal message (optional)" :value="$questionnaire->intro_message" rows="4" class="sm:col-span-2" hint="Shown at the top of the questionnaire instead of the standard introduction." />
        </section>

        <section class="a-card a-card-body space-y-5" aria-label="Questionnaire options">
            <x-admin.checkbox-list name="sections" label="Sections to include" :options="\App\Support\Onboarding\QuestionnaireForm::optionalSections()" :selected="$questionnaire->sections ?? []"
                hint="“About you” and “Anything else” are always included. Untick sections that do not apply to this client." />
            <x-admin.input name="expires_at" type="date" label="Link expires on" :value="$questionnaire->expires_at?->format('Y-m-d')" hint="Leave empty for a link that never expires. Saved answers are kept either way." class="max-w-xs" />
        </section>

        @include('admin.partials.save-bar', ['label' => $questionnaire->exists ? 'Save changes' : 'Create questionnaire'])
    </form>
@endsection
