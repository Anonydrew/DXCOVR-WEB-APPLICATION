@extends('layouts.admin')

@section('title', 'Clients')

@section('content')
    <x-admin.page-header title="Clients" description="The people and organisations you invoice. Open a client to see their invoices and what they owe.">
        <x-slot:actions>
            <a href="{{ route('admin.invoices.index') }}" class="a-btn"><x-icon name="receipt" :size="16" /> All invoices</a>
            @can('invoices.manage')
                <a href="{{ route('admin.clients.create') }}" class="a-btn a-btn-primary"><x-icon name="plus" :size="16" /> New client</a>
            @endcan
        </x-slot:actions>
    </x-admin.page-header>

    <div class="a-card overflow-hidden">
        <x-admin.filters :action="route('admin.clients.index')" placeholder="Search name, organisation, email or phone" />

        @if ($clients->isEmpty())
            <div class="p-6">
                <x-admin.empty-state :title="request('q') ? 'No clients match your search' : 'No clients yet'" icon="user">
                    Add a client with their billing details, then create invoices for them.
                    @if (! request('q'))
                        @can('invoices.manage')
                            <x-slot:actions>
                                <a href="{{ route('admin.clients.create') }}" class="a-btn a-btn-primary"><x-icon name="plus" :size="16" /> Add your first client</a>
                            </x-slot:actions>
                        @endcan
                    @endif
                </x-admin.empty-state>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="a-table">
                    <thead><tr><th scope="col">Client</th><th scope="col">Email</th><th scope="col">Invoices</th><th scope="col" class="!text-right">Outstanding</th></tr></thead>
                    <tbody>
                        @foreach ($clients as $client)
                            @php
                                $outstanding = $client->invoices->groupBy('currency')->map(fn ($invoices) => $invoices->sum(fn ($invoice) => $invoice->balance()))->filter();
                                $overdue = $client->invoices->contains(fn ($invoice) => $invoice->isOverdue());
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('admin.clients.show', $client) }}" class="font-semibold hover:text-lime">{{ $client->displayName() }}</a>
                                    @if ($client->company)
                                        <p class="text-xs a-subtle">{{ $client->name }}</p>
                                    @endif
                                </td>
                                <td class="a-muted">{{ $client->email ?: '—' }}</td>
                                <td class="a-muted">{{ $client->invoices_count }}</td>
                                <td class="whitespace-nowrap text-right">
                                    @forelse ($outstanding as $currency => $amount)
                                        <span class="block {{ $overdue ? 'text-danger' : '' }}">{{ \App\Support\Money::format($amount, $currency) }}</span>
                                    @empty
                                        <span class="a-subtle">Nothing due</span>
                                    @endforelse
                                    @if ($overdue)
                                        <span class="text-xs text-danger">Overdue</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $clients->links('admin.partials.pagination') }}
        @endif
    </div>
@endsection
