@extends('layouts.admin')

@section('title', 'Team')

@section('content')
    <x-admin.page-header title="Team" description="Team profiles shown on the About page. Only add people who have agreed to be listed.">
        <x-slot:actions>
            @if ($canManage)
                <a href="{{ route('admin.founder.edit') }}" class="a-btn">Founder profile</a>
                <a href="{{ route('admin.team.create') }}" class="a-btn a-btn-primary"><x-icon name="plus" :size="16" /> Add team member</a>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    @if ($founder)
        <div class="a-card mb-6 flex items-center gap-4 p-4">
            <span class="grid size-11 place-items-center rounded-full bg-lime font-display font-bold text-ink">{{ $founder->initials() }}</span>
            <div class="flex-1">
                <p class="font-semibold">{{ $founder->name }} <span class="a-badge a-badge-success ml-1">Founder</span></p>
                <p class="text-xs a-subtle">{{ $founder->role_title }} · {{ $founder->is_published ? 'Published' : 'Hidden' }}</p>
            </div>
            @if ($canManage)
                <a href="{{ route('admin.founder.edit') }}" class="a-btn a-btn-sm">Edit founder profile</a>
            @endif
        </div>
    @endif

    @if ($members->isEmpty())
        <x-admin.empty-state title="No team members" icon="users">The Team section on the About page stays hidden until at least one published profile exists.</x-admin.empty-state>
    @else
        <div class="a-card overflow-hidden">
            <ul class="divide-y divide-line" @if ($canManage) x-data="sortableList(@js(route('admin.reorder', 'team')))" @endif>
                @foreach ($members as $member)
                    <li class="flex items-center gap-4 px-4 py-3" data-id="{{ $member->id }}">
                        @if ($canManage)
                            <span class="a-drag-handle a-icon-btn" aria-hidden="true"><x-icon name="drag" :size="16" /></span>
                        @endif
                        <div class="a-thumb size-11 flex-none !rounded-full">
                            @if ($member->photo)
                                <img src="{{ $member->photo->variantUrl(480) }}" alt="">
                            @else
                                <span class="grid h-full place-items-center text-xs font-bold">{{ $member->initials() }}</span>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold">{{ $member->name }}</p>
                            <p class="truncate text-xs a-subtle">{{ $member->role_title }}</p>
                        </div>
                        <span class="a-badge {{ $member->is_published ? 'a-badge-success' : 'a-badge-muted' }}">{{ $member->is_published ? 'Published' : 'Hidden' }}</span>
                        @if ($canManage)
                            <a href="{{ route('admin.team.edit', $member) }}" class="a-icon-btn" aria-label="Edit {{ $member->name }}"><x-icon name="edit" :size="16" /></a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
@endsection
