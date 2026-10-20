@extends('layouts.admin')

@section('title', $role->exists ? 'Edit role' : 'New role')

@section('content')
    <x-admin.page-header :title="$role->exists ? $role->name : 'New role'" :back="route('admin.roles.index')" />

    @php($chosen = old('permissions', $selected))

    <form method="POST" action="{{ $role->exists ? route('admin.roles.update', $role) : route('admin.roles.store') }}" class="max-w-4xl space-y-6">
        @csrf
        @if ($role->exists) @method('PUT') @endif

        <div class="a-card a-card-body">
            <x-admin.input name="name" label="Role name" :value="$role->name" required maxlength="60" />
        </div>

        <input type="hidden" name="permissions" value="">
        <div class="grid gap-4 md:grid-cols-2">
            @foreach ($groups as $group => $permissions)
                <fieldset class="a-card a-card-body">
                    <legend class="sr-only">{{ $group }}</legend>
                    <p class="a-card-title mb-3">{{ $group }}</p>
                    <div class="space-y-2.5">
                        @foreach ($permissions as $permission => $description)
                            <label class="flex items-start gap-2.5 text-sm">
                                <input type="checkbox" name="permissions[]" value="{{ $permission }}" class="a-checkbox" @checked(in_array($permission, (array) $chosen, true))>
                                <span>{{ $description }} <code class="block text-[0.7rem] a-subtle">{{ $permission }}</code></span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            @endforeach
        </div>
        @error('permissions.*')<p class="a-error">{{ $message }}</p>@enderror

        @include('admin.partials.save-bar', ['label' => 'Save role'])
    </form>
@endsection
