@extends('layouts.admin')

@section('title', 'Enquiries')

@section('content')
    <x-admin.page-header title="Enquiries" description="Project enquiries, contact messages and product demo requests.">
        <x-slot:actions>
            @can('exports.run')
                <a href="{{ route('admin.enquiries.export', request()->only(['status', 'type', 'q', 'unread'])) }}" class="a-btn"><x-icon name="download" :size="16" /> Export CSV</a>
            @endcan
        </x-slot:actions>
    </x-admin.page-header>

    <nav class="mb-6 flex flex-wrap gap-2" aria-label="Status">
        <a href="{{ route('admin.enquiries.index') }}" class="a-btn a-btn-sm {{ ! request()->hasAny(['status', 'unread']) ? 'a-btn-primary' : '' }}">All</a>
        <a href="{{ route('admin.enquiries.index', ['unread' => 1]) }}" class="a-btn a-btn-sm {{ request('unread') ? 'a-btn-primary' : '' }}">Unread ({{ $unreadCount }})</a>
        @foreach (\App\Enums\EnquiryStatus::cases() as $status)
            <a href="{{ route('admin.enquiries.index', ['status' => $status->value]) }}" class="a-btn a-btn-sm {{ request('status') === $status->value ? 'a-btn-primary' : '' }}">{{ $status->label() }} ({{ $statusCounts[$status->value] ?? 0 }})</a>
        @endforeach
    </nav>

    <div class="a-card overflow-hidden">
        <x-admin.filters :action="route('admin.enquiries.index')" placeholder="Search name, email, organisation or message">
            @foreach (['status', 'unread'] as $keep)
                @if (request($keep))
                    <input type="hidden" name="{{ $keep }}" value="{{ request($keep) }}">
                @endif
            @endforeach
            <div>
                <label for="filter-type" class="sr-only">Type</label>
                <select id="filter-type" name="type" class="a-input">
                    <option value="">All types</option>
                    @foreach (\App\Enums\EnquiryType::options() as $value => $label)
                        <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </x-admin.filters>

        @if ($enquiries->isEmpty())
            <div class="p-6"><x-admin.empty-state title="No enquiries" icon="inbox">Messages from the contact and product forms appear here.</x-admin.empty-state></div>
        @else
            <div class="overflow-x-auto">
                <table class="a-table">
                    <thead><tr><th scope="col">From</th><th scope="col">Type</th><th scope="col">Interest</th><th scope="col">Status</th><th scope="col">Received</th></tr></thead>
                    <tbody>
                        @foreach ($enquiries as $enquiry)
                            <tr class="{{ $enquiry->read_at ? '' : 'font-semibold' }}">
                                <td>
                                    <a href="{{ route('admin.enquiries.show', $enquiry) }}" class="hover:text-lime">
                                        @unless ($enquiry->read_at)<span class="mr-1.5 inline-block size-2 rounded-full bg-lime" aria-label="Unread"></span>@endunless
                                        {{ $enquiry->name }}
                                    </a>
                                    <p class="text-xs font-normal a-subtle">{{ $enquiry->organisation ?: $enquiry->email }}</p>
                                </td>
                                <td class="font-normal a-muted">{{ $enquiry->type->label() }}</td>
                                <td class="font-normal a-muted">{{ $enquiry->product?->name ?? $enquiry->service?->title ?? 'Not set' }}</td>
                                <td><x-admin.status-badge :status="$enquiry->status" /></td>
                                <td class="whitespace-nowrap font-normal a-muted" title="{{ $enquiry->created_at->format('j M Y H:i') }}">{{ $enquiry->created_at->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $enquiries->links('admin.partials.pagination') }}
        @endif
    </div>
@endsection
