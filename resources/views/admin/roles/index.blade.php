@extends('layouts.admin')

@section('title', 'Roles & permissions')

@section('content')
    <x-admin.page-header title="Roles & permissions" description="Roles bundle permissions. Every permission is enforced on the server, not just hidden in the interface.">
        <x-slot:actions>
            <a href="{{ route('admin.roles.create') }}" class="a-btn a-btn-primary"><x-icon name="plus" :size="16" /> New role</a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($roles as $role)
            @php($isSuper = $role->name === \App\Support\Permissions::SUPER_ADMIN_ROLE)
            <article class="a-card flex flex-col p-5">
                <div class="flex items-start justify-between gap-3">
                    <h2 class="font-display text-lg font-semibold">{{ $role->name }}</h2>
                    <x-icon name="shield" :size="18" class="{{ $isSuper ? 'text-lime' : 'a-subtle' }}" />
                </div>
                <p class="mt-2 flex-1 text-sm a-muted">{{ $descriptions[$role->name] ?? 'Custom role.' }}</p>
                <p class="mt-4 text-xs a-subtle">{{ $role->users_count }} {{ \Illuminate\Support\Str::plural('user', $role->users_count) }} · {{ $isSuper ? 'All permissions' : $role->permissions_count.' permissions' }}</p>
                <div class="mt-4 flex gap-2">
                    @unless ($isSuper)
                        <a href="{{ route('admin.roles.edit', $role) }}" class="a-btn a-btn-sm">Edit</a>
                        @if ($role->users_count === 0)
                            <x-admin.delete-button :action="route('admin.roles.destroy', $role)" confirm="Delete this role?" />
                        @endif
                    @endunless
                </div>
            </article>
        @endforeach
    </div>
@endsection
