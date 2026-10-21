@extends('layouts.admin')

@section('title', 'Invoices')

@section('content')
    <x-admin.page-header title="Invoices" :description="$client ? 'Invoices for '.$client->displayName().'.' : 'Create invoices for your clients, share them by link or email, and record payments as they come in.'">
        <x-slot:actions>
            @can('invoices.manage')
                <a href="{{ route('admin.settings.edit', 'invoicing') }}" class="a-btn"><x-icon name="settings" :size="16" /> Invoice settings</a>
                <a href="{{ route('admin.invoices.create', $client ? ['client' => $client->id] : []) }}" class="a-btn a-btn-primary"><x-icon name="plus" :size="16" /> New invoice</a>
            @endcan
        </x-slot:actions>
    </x-admin.page-header>

    @if ($outstanding->isNotEmpty() || $overdueCount > 0)
        <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($outstanding as $currency => $amount)
                <div class="a-card p-5">
                    <p class="a-mono a-subtle">Outstanding · {{ $currency }}</p>
                    <p class="mt-2 font-display text-2xl font-semibold">{{ \App\Support\Money::format((int) $amount, $currency) }}</p>
                </div>
            @endforeach
            @if ($overdueCount > 0)
                <a href="{{ route('admin.invoices.index', ['status' => 'overdue']) }}" class="a-card p-5 transition hover:border-line-strong">
                    <p class="a-mono a-subtle">Overdue</p>
                    <p class="mt-2 font-display text-2xl font-semibold text-danger">{{ trans_choice(':count invoice|:count invoices', $overdueCount) }}</p>
                </a>
            @endif
        </div>
    @endif

    @php $filter = fn (?string $status) => route('admin.invoices.index', array_filter(['status' => $status, 'client' => $client?->id, 'q' => request('q')])); @endphp
    <nav class="mb-6 flex flex-wrap gap-2" aria-label="Status">
        <a href="{{ $filter(null) }}" class="a-btn a-btn-sm {{ ! request('status') ? 'a-btn-primary' : '' }}">All</a>
        @foreach (\App\Enums\InvoiceStatus::cases() as $status)
            <a href="{{ $filter($status->value) }}" class="a-btn a-btn-sm {{ request('status') === $status->value ? 'a-btn-primary' : '' }}">{{ $status->label() }} ({{ $statusCounts[$status->value] ?? 0 }})</a>
        @endforeach
        <a href="{{ $filter('overdue') }}" class="a-btn a-btn-sm {{ request('status') === 'overdue' ? 'a-btn-primary' : '' }}">Overdue ({{ $overdueCount }})</a>
    </nav>

    <div class="a-card overflow-hidden">
        <x-admin.filters :action="route('admin.invoices.index')" placeholder="Search invoice number, subject or client">
            @if (request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
            @if ($client)
                <input type="hidden" name="client" value="{{ $client->id }}">
            @endif
        </x-admin.filters>

        @if ($invoices->isEmpty())
            <div class="p-6">
                <x-admin.empty-state :title="request()->hasAny(['q', 'status']) ? 'No invoices match' : 'No invoices yet'" icon="receipt">
                    Create an invoice for a client, then send it by email or share its private link.
                </x-admin.empty-state>
            </div>
        @else
            @include('admin.invoices.partials.table', ['invoices' => $invoices, 'showClient' => true])
        @endif
    </div>
@endsection
