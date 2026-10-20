@extends('layouts.admin')

@section('title', 'Job listings')

@section('content')
    <x-admin.page-header title="Job listings" description="Roles advertised on the Careers page.">
        <x-slot:actions>
            @if ($canManage)
                <a href="{{ route('admin.jobs.create') }}" class="a-btn a-btn-primary"><x-icon name="plus" :size="16" /> New job listing</a>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    @if ($canManage)
        @include('admin.partials.tabs', ['tabs' => [['label' => 'Job listings', 'route' => 'admin.jobs.index'], ['label' => 'Departments', 'route' => 'admin.departments.index']]])
    @endif

    <div class="a-card overflow-hidden">
        <x-admin.filters :action="route('admin.jobs.index')" placeholder="Search roles">
            <div>
                <label for="filter-status" class="sr-only">Status</label>
                <select id="filter-status" name="status" class="a-input">
                    <option value="">All statuses</option>
                    @foreach (\App\Enums\PublicationStatus::options() as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filter-state" class="sr-only">Applications</label>
                <select id="filter-state" name="state" class="a-input">
                    <option value="">Open and closed</option>
                    <option value="open" @selected(request('state') === 'open')>Accepting applications</option>
                    <option value="closed" @selected(request('state') === 'closed')>Closed</option>
                </select>
            </div>
        </x-admin.filters>

        @if ($jobs->isEmpty())
            <div class="p-6"><x-admin.empty-state title="No job listings" icon="briefcase">When you are hiring, create a listing. The Careers page shows a clear “no open roles” message in the meantime.</x-admin.empty-state></div>
        @else
            <div class="overflow-x-auto">
                <table class="a-table">
                    <thead><tr><th scope="col">Role</th><th scope="col">Status</th><th scope="col">Applications</th><th scope="col">Deadline</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead>
                    <tbody>
                        @foreach ($jobs as $job)
                            <tr>
                                <td>
                                    @if ($canManage)
                                        <a href="{{ route('admin.jobs.edit', $job) }}" class="font-semibold hover:text-lime">{{ $job->title }}</a>
                                    @else
                                        <span class="font-semibold">{{ $job->title }}</span>
                                    @endif
                                    <p class="text-xs a-subtle">{{ $job->department?->name ?? 'No department' }} · {{ $job->location }} · {{ $job->employmentTypeLabel() }}</p>
                                </td>
                                <td>
                                    <x-admin.status-badge :status="$job->status" />
                                    @if ($job->isPubliclyVisible())
                                        <span class="a-badge {{ $job->isAcceptingApplications() ? 'a-badge-success' : 'a-badge-muted' }} ml-1">{{ $job->isAcceptingApplications() ? 'Open' : 'Closed' }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($canViewApplications && $job->applications_count > 0)
                                        <a href="{{ route('admin.applications.index', ['job' => $job->id]) }}" class="hover:text-lime">{{ $job->applications_count }}</a>
                                    @else
                                        {{ $job->applications_count }}
                                    @endif
                                </td>
                                <td class="whitespace-nowrap a-muted">{{ $job->application_deadline?->format('j M Y, H:i') ?? 'None' }}</td>
                                <td class="text-right">
                                    @if ($canManage)
                                        <a href="{{ route('admin.jobs.edit', $job) }}" class="a-icon-btn" aria-label="Edit {{ $job->title }}"><x-icon name="edit" :size="16" /></a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $jobs->links('admin.partials.pagination') }}
        @endif
    </div>
@endsection
