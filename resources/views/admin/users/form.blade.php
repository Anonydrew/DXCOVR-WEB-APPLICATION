@extends('layouts.admin')

@section('title', $user->exists ? 'Edit user' : 'Invite user')

@section('content')
    <x-admin.page-header :title="$user->exists ? $user->name : 'Invite a user'" :description="$user->exists ? $user->email : 'They will receive an email with a link to choose their password.'" :back="route('admin.users.index')">
        <x-slot:actions>
            @if ($user->exists && ! $user->is(auth()->user()))
                <form method="POST" action="{{ route('admin.users.send-reset', $user) }}">@csrf <button type="submit" class="a-btn">Send password link</button></form>
                <form method="POST" action="{{ route('admin.users.toggle-active', $user) }}" data-confirm="{{ $user->is_active ? 'Deactivate this account? They will be signed out immediately.' : 'Reactivate this account?' }}">
                    @csrf
                    <button type="submit" class="a-btn {{ $user->is_active ? 'a-btn-danger' : '' }}">{{ $user->is_active ? 'Deactivate' : 'Activate' }}</button>
                </form>
                <x-admin.delete-button :action="route('admin.users.destroy', $user)" confirm="Delete this account permanently? Content they created is kept." size="md" />
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" class="max-w-3xl space-y-6">
        @csrf
        @if ($user->exists) @method('PUT') @endif

        <div class="a-card a-card-body grid gap-5 sm:grid-cols-2">
            <x-admin.input name="name" label="Full name" :value="$user->name" required />
            <x-admin.input name="email" type="email" label="Email address" :value="$user->email" required />
            <x-admin.input name="job_title" label="Job title" :value="$user->job_title" class="sm:col-span-2" />
        </div>

        <div class="a-card a-card-body">
            <x-admin.checkbox-list name="roles" label="Roles" :options="$roles" :selected="$user->exists ? $user->roles->pluck('name')->all() : []" :columns="1" hint="Users receive every permission from all of their roles." />
        </div>

        @include('admin.partials.save-bar', ['label' => $user->exists ? 'Save user' : 'Create and send invitation'])
    </form>
@endsection
