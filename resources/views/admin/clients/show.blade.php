@extends('layouts.admin')

@section('title', $client->displayName())

@section('content')
    <x-admin.page-header :title="$client->displayName()" :description="$client->company ? $client->name : null" :back="route('admin.clients.index')">
        <x-slot:actions>
            @can('invoices.manage')
                <a href="{{ route('admin.clients.edit', $client) }}" class="a-btn"><x-icon name="edit" :size="16" /> Edit</a>
                @if ($invoices->total() === 0)
                    <x-admin.delete-button :action="route('admin.clients.destroy', $client)" confirm="Delete this client permanently?" />
                @endif
                <a href="{{ route('admin.invoices.create', ['client' => $client->id]) }}" class="a-btn a-btn-primary"><x-icon name="plus" :size="16" /> New invoice</a>
            @endcan
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
        <div class="min-w-0 space-y-6">
            @if ($balances !== [])
                <div class="grid gap-4 sm:grid-cols-3">
                    @foreach ($balances as $currency => $balance)
                        <div class="a-card p-5">
                            <p class="a-mono a-subtle">Invoiced · {{ $currency }}</p>
                            <p class="mt-2 font-display text-2xl font-semibold">{{ \App\Support\Money::format($balance['invoiced'], $currency) }}</p>
                        </div>
                        <div class="a-card p-5">
                            <p class="a-mono a-subtle">Paid</p>
                            <p class="mt-2 font-display text-2xl font-semibold">{{ \App\Support\Money::format($balance['paid'], $currency) }}</p>
                        </div>
                        <div class="a-card p-5">
                            <p class="a-mono a-subtle">Outstanding</p>
                            <p class="mt-2 font-display text-2xl font-semibold {{ $balance['outstanding'] > 0 ? 'text-lime' : '' }}">{{ \App\Support\Money::format($balance['outstanding'], $currency) }}</p>
                        </div>
                    @endforeach
                </div>
            @endif

            <section class="a-card overflow-hidden" aria-labelledby="invoices-heading">
                <div class="a-card-header"><h2 id="invoices-heading" class="a-card-title">Invoices</h2></div>
                @if ($invoices->isEmpty())
                    <div class="p-6">
                        <x-admin.empty-state title="No invoices yet" icon="receipt">
                            Invoices you create for {{ $client->displayName() }} appear here.
                        </x-admin.empty-state>
                    </div>
                @else
                    @include('admin.invoices.partials.table', ['invoices' => $invoices, 'showClient' => false])
                @endif
            </section>
        </div>

        <aside class="space-y-6">
            <section class="a-card a-card-body space-y-3 text-sm" aria-labelledby="details-heading">
                <h2 id="details-heading" class="a-card-title">Billing details</h2>
                <dl class="space-y-3">
                    @foreach ([
                        'Contact' => $client->name,
                        'Email' => $client->email,
                        'Phone' => $client->phone,
                        'Address' => $client->address,
                        'Tax ID' => $client->tax_number,
                        'Currency' => $client->defaultCurrency(),
                    ] as $label => $value)
                        @if (filled($value))
                            <div>
                                <dt class="a-mono a-subtle">{{ $label }}</dt>
                                @if ($label === 'Email')
                                    <dd class="mt-0.5 break-all"><a href="mailto:{{ $value }}" class="hover:text-lime">{{ $value }}</a></dd>
                                @else
                                    <dd class="mt-0.5 whitespace-pre-line">{{ $value }}</dd>
                                @endif
                            </div>
                        @endif
                    @endforeach
                </dl>
            </section>

            @if ($client->notes)
                <section class="a-card a-card-body">
                    <h2 class="a-card-title">Internal notes</h2>
                    <p class="mt-3 whitespace-pre-line text-sm">{{ $client->notes }}</p>
                </section>
            @endif
        </aside>
    </div>
@endsection
