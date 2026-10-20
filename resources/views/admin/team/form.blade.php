@extends('layouts.admin')

@php($isFounder = $isFounder ?? false)

@section('title', $isFounder ? 'Founder profile' : ($member->exists ? 'Edit team member' : 'Add team member'))

@section('content')
    <x-admin.page-header
        :title="$isFounder ? 'Founder profile' : ($member->exists ? $member->name : 'Add team member')"
        :description="$isFounder ? 'Shown at /about/founder and in the About and homepage introductions. Include only verified information.' : null"
        :back="$isFounder ? null : route('admin.team.index')">
        <x-slot:actions>
            @if ($isFounder && $member->exists && $member->is_published)
                <a href="{{ route('about.founder') }}" target="_blank" rel="noopener" class="a-btn"><x-icon name="external" :size="16" /> View profile</a>
            @endif
            @if (! $isFounder && $member->exists)
                <x-admin.delete-button :action="route('admin.team.destroy', $member)" confirm="Remove this team member from the website?" label="Remove" />
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <form method="POST" action="{{ $isFounder ? route('admin.founder.update') : ($member->exists ? route('admin.team.update', $member) : route('admin.team.store')) }}" class="max-w-4xl space-y-6" data-dirty-guard>
        @csrf
        @if ($isFounder || $member->exists) @method('PUT') @endif

        <div class="a-card a-card-body grid gap-5 sm:grid-cols-2">
            <x-admin.input name="name" label="Name" :value="$member->name" required maxlength="160" id="f-title" />
            <x-admin.input name="role_title" label="Professional title" :value="$member->role_title" maxlength="160" />
            <div class="sm:col-span-2">@include('admin.partials.slug', ['model' => $member, 'prefix' => 'Profile slug: '])</div>
            <x-admin.textarea name="short_bio" label="Short introduction" :value="$member->short_bio" rows="2" counter="400" class="sm:col-span-2" />
            <x-admin.media-field name="photo_media_id" label="Portrait" :media="$member->photo" class="sm:col-span-2" hint="A square or portrait-orientation photo works best." />
        </div>

        <div class="a-card a-card-body space-y-6">
            <x-admin.rich-editor name="bio" label="Biography" :value="$member->bio" compact />
            <x-admin.list-field name="expertise" label="Areas of expertise" :items="$member->expertise" placeholder="e.g. Systems thinking" :max="20" />
            <x-admin.list-field name="skills" label="Skills" :items="$member->skills" placeholder="e.g. Laravel" :max="40" />
            <x-admin.repeater name="experience" label="Professional experience" :rows="$member->experience ?? []" :max="30" add-label="Add role"
                :fields="['role' => ['label' => 'Role', 'max' => 160], 'organisation' => ['label' => 'Organisation', 'max' => 160], 'period' => ['label' => 'Period', 'max' => 60], 'summary' => ['label' => 'Summary', 'type' => 'textarea', 'max' => 600]]"
                hint="Only include roles and dates you can verify." />
            <x-admin.repeater name="links" label="Professional links" :rows="$member->links ?? []" :max="12" add-label="Add link"
                :fields="['label' => ['label' => 'Label', 'max' => 80], 'url' => ['label' => 'Address (https://…)', 'type' => 'url', 'max' => 500]]" />
        </div>

        <div class="a-card a-card-body grid gap-5 sm:grid-cols-2">
            <x-admin.checkbox-list name="projects" label="Selected projects" :options="$projects" :selected="$member->projects?->modelKeys() ?? []" class="sm:col-span-2" />
            <x-admin.select name="user_id" label="Linked admin account" :options="$users" :value="$member->user_id" placeholder="Not linked" hint="Articles written by this account appear on the profile." />
            <x-admin.toggle name="is_published" label="Show on the website" :checked="$member->is_published" class="sm:self-end" />
        </div>

        @include('admin.partials.save-bar', ['label' => 'Save profile'])
    </form>
@endsection
