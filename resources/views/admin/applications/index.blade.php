@extends('layouts.admin')

@section('title', 'Applications')

@section('content')
    <x-admin.page-header title="Applications" description="Applicant information is confidential. Access and downloads are recorded in the audit log.">
        <x-slot:actions>
            @can('exports.run')
                <a href="{{ route('admin.applications.export', request()->only(['job', 'stage', 'q'])) }}" class="a-btn"><x-icon name="download" :size="16" /> Export CSV</a>
            @endcan
        </x-slot:actions>
    </x-admin.page-header>

    <nav class="mb-6 flex flex-wrap gap-2" aria-label="Stages">
        <a href="{{ route('admin.applications.index', request()->except(['stage', 'page'])) }}" class="a-btn a-btn-sm {{ ! request('stage') ? 'a-btn-primary' : '' }}">All ({{ $stageCounts->sum() }})</a>
        @foreach (\App\Enums\ApplicationStage::cases() as $stage)
            <a href="{{ route('admin.applications.index', array_merge(request()->except('page'), ['stage' => $stage->value])) }}" class="a-btn a-btn-sm {{ request('stage') === $stage->value ? 'a-btn-primary' : '' }}">{{ $stage->label() }} ({{ $stageCounts[$stage->value] ?? 0 }})</a>
        @endforeach
    </nav>

    <div class="a-card overflow-hidden">
        <x-admin.filters :action="route('admin.applications.index')" placeholder="Search name, email or reference">
            @if (request('stage'))
                <input type="hidden" name="stage" value="{{ request('stage') }}">
            @endif
            <div>
                <label for="filter-job" class="sr-only">Role</label>
                <select id="filter-job" name="job" class="a-input">
                    <option value="">All roles</option>
                    @foreach ($jobs as $id => $title)
                        <option value="{{ $id }}" @selected((string) request('job') === (string) $id)>{{ $title }}</option>
                    @endforeach
                </select>
            </div>
        </x-admin.filters>

        @if ($applications->isEmpty())
            <div class="p-6"><x-admin.empty-state title="No applications" icon="users">Applications submitted through the Careers page will appear here.</x-admin.empty-state></div>
        @else
            <div class="overflow-x-auto">
                <table class="a-table">
                    <thead><tr><th scope="col">Applicant</th><th scope="col">Role</th><th scope="col">Stage</th><th scope="col">Received</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead>
                    <tbody>
                        @foreach ($applications as $application)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.applications.show', $application) }}" class="font-semibold hover:text-lime">{{ $application->name }}</a>
                                    <p class="text-xs a-subtle">{{ $application->email }}</p>
                                </td>
                                <td class="a-muted">{{ $application->jobListing?->title }}</td>
                                <td><x-admin.status-badge :status="$application->stage" /></td>
                                <td class="whitespace-nowrap a-muted" title="{{ $application->created_at->format('j M Y H:i') }}">{{ $application->created_at->diffForHumans() }}</td>
                                <td class="text-right"><a href="{{ route('admin.applications.show', $application) }}" class="a-icon-btn" aria-label="Open application from {{ $application->name }}"><x-icon name="eye" :size="16" /></a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $applications->links('admin.partials.pagination') }}
        @endif
    </div>
@endsection
