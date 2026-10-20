@extends('layouts.admin')

@section('title', 'Announcements')

@section('content')
    <x-admin.page-header title="Announcements" description="A dismissible banner shown at the top of every public page. The most recent active announcement is displayed.">
        <x-slot:actions>
            @if ($canManage)
                <a href="{{ route('admin.announcements.create') }}" class="a-btn a-btn-primary"><x-icon name="plus" :size="16" /> New announcement</a>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    @if ($announcements->isEmpty())
        <x-admin.empty-state title="No announcements" icon="megaphone">Use announcements for company news, product launches or important notices.</x-admin.empty-state>
    @else
        <div class="a-card overflow-x-auto">
            <table class="a-table">
                <thead><tr><th scope="col">Title</th><th scope="col">Window</th><th scope="col">Status</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead>
                <tbody>
                    @foreach ($announcements as $announcement)
                        <tr>
                            <td class="font-semibold">{{ $announcement->title }}</td>
                            <td class="a-muted">{{ $announcement->starts_at?->format('j M Y H:i') ?? 'Now' }} → {{ $announcement->ends_at?->format('j M Y H:i') ?? 'No end' }}</td>
                            <td>
                                @if ($announcement->isCurrent())
                                    <span class="a-badge a-badge-success">Showing</span>
                                @elseif ($announcement->is_active)
                                    <span class="a-badge a-badge-info">Active, outside window</span>
                                @else
                                    <span class="a-badge a-badge-muted">Inactive</span>
                                @endif
                            </td>
                            <td class="text-right">
                                @if ($canManage)
                                    <a href="{{ route('admin.announcements.edit', $announcement) }}" class="a-icon-btn" aria-label="Edit {{ $announcement->title }}"><x-icon name="edit" :size="16" /></a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
