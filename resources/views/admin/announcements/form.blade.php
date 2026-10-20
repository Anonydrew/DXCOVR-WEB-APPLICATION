@extends('layouts.admin')

@section('title', $announcement->exists ? 'Edit announcement' : 'New announcement')

@section('content')
    <x-admin.page-header :title="$announcement->exists ? 'Edit announcement' : 'New announcement'" :back="route('admin.announcements.index')">
        <x-slot:actions>
            @if ($announcement->exists)
                <x-admin.delete-button :action="route('admin.announcements.destroy', $announcement)" />
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <form method="POST" action="{{ $announcement->exists ? route('admin.announcements.update', $announcement) : route('admin.announcements.store') }}" class="max-w-3xl space-y-6">
        @csrf
        @if ($announcement->exists) @method('PUT') @endif

        <div class="a-card a-card-body grid gap-5 sm:grid-cols-2">
            <x-admin.input name="title" label="Headline" :value="$announcement->title" required maxlength="160" class="sm:col-span-2" />
            <x-admin.textarea name="body" label="Supporting text" :value="$announcement->body" rows="2" counter="300" class="sm:col-span-2" />
            <x-admin.input name="link_label" label="Link label" :value="$announcement->link_label" maxlength="60" placeholder="Learn more" />
            <x-admin.input name="link_url" label="Link destination" :value="$announcement->link_url" placeholder="/insights/…" />
            <x-admin.input name="starts_at" label="Show from" type="datetime-local" :value="$announcement->starts_at" hint="Leave blank to start immediately." />
            <x-admin.input name="ends_at" label="Show until" type="datetime-local" :value="$announcement->ends_at" hint="Leave blank to show until deactivated." />
            <x-admin.toggle name="is_active" label="Active" :checked="$announcement->is_active" class="sm:col-span-2" />
        </div>

        @include('admin.partials.save-bar', ['label' => 'Save announcement'])
    </form>
@endsection
