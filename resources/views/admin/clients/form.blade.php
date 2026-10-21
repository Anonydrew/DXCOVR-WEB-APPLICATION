@extends('layouts.admin')

@section('title', $client->exists ? 'Edit client' : 'New client')

@section('content')
    <x-admin.page-header
        :title="$client->exists ? 'Edit '.$client->displayName() : 'New client'"
        description="These details appear in the “Bill to” section of new invoices."
        :back="$client->exists ? route('admin.clients.show', $client) : route('admin.clients.index')" />

    <form method="POST" action="{{ $client->exists ? route('admin.clients.update', $client) : route('admin.clients.store') }}" class="max-w-3xl space-y-6" data-dirty-guard>
        @csrf
        @if ($client->exists) @method('PUT') @endif

        <section class="a-card a-card-body grid gap-5 sm:grid-cols-2" aria-label="Contact">
            <x-admin.input name="name" label="Contact name" :value="$client->name" required maxlength="160" />
            <x-admin.input name="company" label="Organisation" :value="$client->company" maxlength="160" hint="Leave empty when you invoice a person." />
            <x-admin.input name="email" type="email" label="Email" :value="$client->email" hint="Invoices are emailed here." />
            <x-admin.input name="phone" label="Phone" :value="$client->phone" maxlength="40" />
            <x-admin.textarea name="address" label="Billing address" :value="$client->address" rows="3" class="sm:col-span-2" />
        </section>

        <section class="a-card a-card-body grid gap-5 sm:grid-cols-2" aria-label="Billing">
            <x-admin.input name="tax_number" label="Client tax ID (optional)" :value="$client->tax_number" maxlength="60" hint="Printed on invoices when set." />
            <div>
                <x-admin.input name="currency" label="Currency" :value="$client->currency" maxlength="3" list="currency-options" class="max-w-40" :hint="'Leave empty to use your default ('.strtoupper((string) app(\App\Services\SiteSettings::class)->get('invoicing.currency', 'GHS')).').'" />
                <datalist id="currency-options">
                    @foreach (\App\Support\Money::CURRENCIES as $code => $name)
                        <option value="{{ $code }}">{{ $name }}</option>
                    @endforeach
                </datalist>
            </div>
            <x-admin.textarea name="notes" label="Internal notes" :value="$client->notes" rows="3" class="sm:col-span-2" hint="Only visible to your team." />
        </section>

        @include('admin.partials.save-bar', ['label' => $client->exists ? 'Save client' : 'Add client'])
    </form>
@endsection
