@extends('layouts.admin')

@section('title', 'Redirects')

@section('content')
    <x-admin.page-header title="Redirects" description="Send visitors and search engines from old addresses to new ones. Use 301 for permanent moves.">
        <x-slot:actions>
            <a href="{{ route('admin.redirects.create') }}" class="a-btn a-btn-primary"><x-icon name="plus" :size="16" /> New redirect</a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="a-card overflow-hidden">
        <x-admin.filters :action="route('admin.redirects.index')" placeholder="Search paths" />
        @if ($redirects->isEmpty())
            <p class="p-6 text-sm a-muted">No redirects yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="a-table">
                    <thead><tr><th scope="col">From</th><th scope="col">To</th><th scope="col">Type</th><th scope="col">Hits</th><th scope="col">Status</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead>
                    <tbody>
                        @foreach ($redirects as $redirect)
                            <tr>
                                <td class="font-mono text-xs">{{ $redirect->from_path }}</td>
                                <td class="max-w-xs truncate font-mono text-xs">{{ $redirect->to_url }}</td>
                                <td>{{ $redirect->status_code }}</td>
                                <td class="a-muted">{{ $redirect->hits }}@if ($redirect->last_hit_at) <span class="text-xs a-subtle">· {{ $redirect->last_hit_at->diffForHumans() }}</span>@endif</td>
                                <td><span class="a-badge {{ $redirect->is_active ? 'a-badge-success' : 'a-badge-muted' }}">{{ $redirect->is_active ? 'Active' : 'Off' }}</span></td>
                                <td class="text-right"><a href="{{ route('admin.redirects.edit', $redirect) }}" class="a-icon-btn" aria-label="Edit redirect {{ $redirect->from_path }}"><x-icon name="edit" :size="16" /></a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $redirects->links('admin.partials.pagination') }}
        @endif
    </div>
@endsection
