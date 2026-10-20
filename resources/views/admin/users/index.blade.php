@extends('layouts.admin')

@section('title', 'Users')

@section('content')
    <x-admin.page-header title="Users" description="People who can sign in to the admin. New users receive an email to choose their own password.">
        <x-slot:actions>
            <a href="{{ route('admin.roles.index') }}" class="a-btn">Roles & permissions</a>
            <a href="{{ route('admin.users.create') }}" class="a-btn a-btn-primary"><x-icon name="plus" :size="16" /> Invite user</a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="a-card overflow-hidden">
        <x-admin.filters :action="route('admin.users.index')" placeholder="Search name or email" />
        <div class="overflow-x-auto">
            <table class="a-table">
                <thead><tr><th scope="col">User</th><th scope="col">Roles</th><th scope="col">Two-factor</th><th scope="col">Last sign-in</th><th scope="col">Status</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead>
                <tbody>
                    @foreach ($users as $user)
                        @php($protected = $user->isSuperAdmin() && ! auth()->user()->isSuperAdmin())
                        <tr>
                            <td>
                                <p class="font-semibold">{{ $user->name }} @if ($user->is(auth()->user()))<span class="a-badge ml-1">You</span>@endif</p>
                                <p class="text-xs a-subtle">{{ $user->email }}</p>
                            </td>
                            <td class="a-muted">{{ $user->roles->pluck('name')->implode(', ') ?: 'No role' }}</td>
                            <td>
                                <span class="a-badge {{ $user->hasTwoFactorEnabled() ? 'a-badge-success' : 'a-badge-muted' }}">{{ $user->hasTwoFactorEnabled() ? 'On' : 'Off' }}</span>
                            </td>
                            <td class="whitespace-nowrap a-muted">{{ $user->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                            <td><span class="a-badge {{ $user->is_active ? 'a-badge-success' : 'a-badge-danger' }}">{{ $user->is_active ? 'Active' : 'Deactivated' }}</span></td>
                            <td class="whitespace-nowrap text-right">
                                @unless ($protected)
                                    <a href="{{ route('admin.users.edit', $user) }}" class="a-icon-btn" aria-label="Edit {{ $user->name }}"><x-icon name="edit" :size="16" /></a>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $users->links('admin.partials.pagination') }}
    </div>
@endsection
