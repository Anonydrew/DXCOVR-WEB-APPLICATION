@extends('layouts.admin')

@section('title', $redirect->exists ? 'Edit redirect' : 'New redirect')

@section('content')
    <x-admin.page-header :title="$redirect->exists ? 'Edit redirect' : 'New redirect'" :back="route('admin.redirects.index')">
        <x-slot:actions>
            @if ($redirect->exists)
                <x-admin.delete-button :action="route('admin.redirects.destroy', $redirect)" />
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <form method="POST" action="{{ $redirect->exists ? route('admin.redirects.update', $redirect) : route('admin.redirects.store') }}" class="a-card a-card-body max-w-2xl space-y-5">
        @csrf
        @if ($redirect->exists) @method('PUT') @endif
        <x-admin.input name="from_path" label="Old path" :value="$redirect->from_path" required placeholder="/old-page" />
        <x-admin.input name="to_url" label="New destination" :value="$redirect->to_url" required placeholder="/new-page or https://…" />
        <x-admin.select name="status_code" label="Redirect type" :options="[301 => '301: Moved permanently', 302 => '302: Found (temporary)', 307 => '307: Temporary', 308 => '308: Permanent']" :value="$redirect->status_code" required />
        <x-admin.input name="notes" label="Notes" :value="$redirect->notes" maxlength="255" />
        <x-admin.toggle name="is_active" label="Active" :checked="$redirect->is_active" />
        <button type="submit" class="a-btn a-btn-primary">Save redirect</button>
    </form>
@endsection
