@extends('layouts.admin')

@section('title', 'Audit log')

@section('content')
    <x-admin.page-header title="Audit log" description="A record of sign-ins and administrative actions. Entries cannot be edited or deleted from the CMS." />

    <div class="a-card overflow-hidden">
        <x-admin.filters :action="route('admin.audit.index')" placeholder="Search descriptions">
            <div>
                <label for="filter-area" class="sr-only">Area</label>
                <select id="filter-area" name="area" class="a-input">
                    <option value="">All areas</option>
                    @foreach ($areas as $value => $label)
                        <option value="{{ $value }}" @selected(request('area') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filter-user" class="sr-only">User</label>
                <select id="filter-user" name="user" class="a-input">
                    <option value="">All users</option>
                    @foreach ($users as $id => $name)
                        <option value="{{ $id }}" @selected((string) request('user') === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        </x-admin.filters>

        @if ($entries->isEmpty())
            <p class="p-6 text-sm a-muted">No entries match.</p>
        @else
            <div class="overflow-x-auto">
                <table class="a-table">
                    <thead>
                        <tr><th scope="col">When</th><th scope="col">User</th><th scope="col">Action</th><th scope="col">Details</th><th scope="col">IP address</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($entries as $entry)
                            <tr>
                                <td class="whitespace-nowrap a-muted" title="{{ $entry->created_at->toDayDateTimeString() }}">{{ $entry->created_at->format('j M Y, H:i') }}</td>
                                <td class="whitespace-nowrap">{{ $entry->user?->name ?? 'System / visitor' }}</td>
                                <td><code class="text-xs a-muted">{{ $entry->action }}</code></td>
                                <td class="max-w-xl">
                                    {{ $entry->description }}
                                    @if (! empty($entry->properties))
                                        <details class="mt-1">
                                            <summary class="cursor-pointer text-xs a-subtle">Details</summary>
                                            <pre class="mt-1 max-h-48 overflow-auto rounded-lg bg-ink-2 p-2 text-xs">{{ json_encode($entry->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </details>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap text-xs a-subtle">{{ $entry->ip_address }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $entries->links('admin.partials.pagination') }}
        @endif
    </div>
@endsection
