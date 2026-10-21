@extends('layouts.admin')

@section('title', $invoice->exists ? 'Edit '.$invoice->number : 'New invoice')

@php
    $items = old('items', $invoice->exists
        ? $invoice->items->map(fn ($item) => ['description' => $item->description, 'quantity' => $item->quantityLabel(), 'unit_price' => \App\Support\Money::toDecimal($item->unit_price)])->all()
        : [['description' => '', 'quantity' => '1', 'unit_price' => '']]);
    $itemErrors = collect($errors->getMessages())->filter(fn ($messages, $key) => str_starts_with($key, 'items'))->flatten()->unique()->values();
    $clientCurrencies = $clients->mapWithKeys(fn ($client) => [$client->id => $client->currency ? strtoupper($client->currency) : null])->filter();
@endphp

@section('content')
    <x-admin.page-header
        :title="$invoice->exists ? 'Edit invoice '.$invoice->number : 'New invoice'"
        :description="$invoice->exists ? null : 'Saved as a draft. Your client only sees it once you send it.'"
        :back="$invoice->exists ? route('admin.invoices.show', $invoice) : ($invoice->client_id ? route('admin.clients.show', $invoice->client_id) : route('admin.invoices.index'))" />

    @if ($invoice->exists && ! $invoice->isDraft())
        <div class="a-alert a-alert-warning mb-6 max-w-5xl">
            <x-icon name="alert" :size="18" class="mt-0.5 flex-none text-warning" />
            <div>This invoice has already been sent. Your changes show on the client’s link as soon as you save.</div>
        </div>
    @endif

    <form method="POST" action="{{ $invoice->exists ? route('admin.invoices.update', $invoice) : route('admin.invoices.store') }}" class="max-w-5xl space-y-6" data-dirty-guard
        x-data="invoiceForm({
            items: @js(array_values($items)),
            discount: @js((string) old('discount', $invoice->discount ? \App\Support\Money::toDecimal($invoice->discount) : '')),
            taxRate: @js((string) old('tax_rate', rtrim(rtrim((string) $invoice->tax_rate, '0'), '.') ?: '0')),
            currency: @js((string) old('currency', $invoice->currency)),
            clientCurrencies: @js($clientCurrencies),
            defaultCurrency: @js($defaultCurrency),
            followClient: @js(! $invoice->exists),
        })">
        @csrf
        @if ($invoice->exists) @method('PUT') @endif

        <section class="a-card a-card-body grid gap-5 sm:grid-cols-2 lg:grid-cols-3" aria-label="Invoice details">
            <x-admin.select name="client_id" label="Client" :options="$clients->mapWithKeys(fn ($client) => [$client->id => $client->company ? $client->company.' ('.$client->name.')' : $client->name])->all()" :value="$invoice->client_id" placeholder="Choose a client" required class="sm:col-span-2" x-on:change="clientChanged($event.target.value)" />
            <x-admin.input name="number" label="Invoice number" :value="$invoice->number" required maxlength="40" />
            <x-admin.input name="subject" label="Subject (optional)" :value="$invoice->subject" maxlength="200" class="sm:col-span-2 lg:col-span-3" hint="For example “Website redesign, phase 1”." />
            <x-admin.input name="issue_date" type="date" label="Invoice date" :value="$invoice->issue_date" required />
            <x-admin.input name="due_date" type="date" label="Due date" :value="$invoice->due_date" hint="Leave empty for “due on receipt”." />
            <div>
                <x-admin.input name="currency" label="Currency" :value="$invoice->currency" required maxlength="3" list="currency-options" x-model="currency" x-on:input="currency = currency.toUpperCase()" />
                <datalist id="currency-options">
                    @foreach ($currencies as $code => $name)
                        <option value="{{ $code }}">{{ $name }}</option>
                    @endforeach
                </datalist>
            </div>
            @if ($clients->isNotEmpty())
                <p class="text-xs a-subtle sm:col-span-2 lg:col-span-3">Client not listed? <a href="{{ route('admin.clients.create') }}" class="underline hover:text-lime">Add a new client</a> first.</p>
            @endif
        </section>

        <section class="a-card" aria-labelledby="items-heading">
            <div class="a-card-header">
                <h2 id="items-heading" class="a-card-title">Line items</h2>
                <span class="text-xs a-subtle">Prices in <span x-text="currency || '—'"></span></span>
            </div>
            <div class="a-card-body">
                <div class="hidden grid-cols-[minmax(0,1fr)_6rem_9rem_9rem_2.25rem] gap-3 pb-2 md:grid">
                    <span class="a-mono a-subtle">Description</span>
                    <span class="a-mono a-subtle">Qty</span>
                    <span class="a-mono a-subtle">Unit price</span>
                    <span class="a-mono a-subtle text-right">Amount</span>
                    <span></span>
                </div>
                <ol class="space-y-3" x-ref="rows">
                    <template x-for="(item, index) in items" :key="item.key">
                        <li class="grid grid-cols-2 gap-3 rounded-xl border border-line bg-ink-2 p-3 md:grid-cols-[minmax(0,1fr)_6rem_9rem_9rem_2.25rem] md:items-start md:border-0 md:bg-transparent md:p-0">
                            <div class="col-span-2 md:col-span-1">
                                <label class="a-label !text-xs md:sr-only" :for="`item-${index}-description`">Description</label>
                                <textarea :id="`item-${index}-description`" :name="`items[${index}][description]`" x-model="item.description" :rows="Math.min(8, (item.description.match(/\n/g) || []).length + 1)" maxlength="2000" class="a-input !min-h-10 resize-y" placeholder="What you are charging for"></textarea>
                            </div>
                            <div>
                                <span class="a-label !text-xs md:hidden">Qty</span>
                                <input type="text" inputmode="decimal" :name="`items[${index}][quantity]`" x-model="item.quantity" class="a-input text-right tabular-nums" :aria-label="`Quantity for line ${index + 1}`">
                            </div>
                            <div>
                                <span class="a-label !text-xs md:hidden">Unit price</span>
                                <input type="text" inputmode="decimal" :name="`items[${index}][unit_price]`" x-model="item.unit_price" class="a-input text-right tabular-nums" placeholder="0.00" :aria-label="`Unit price for line ${index + 1}`">
                            </div>
                            <div class="col-span-2 flex items-center justify-between gap-2 md:col-span-1 md:block md:pt-2.5 md:text-right">
                                <span class="a-label !mb-0 !text-xs md:hidden">Amount</span>
                                <span class="font-semibold tabular-nums" x-text="format(lineAmount(item))"></span>
                            </div>
                            <div class="col-span-2 flex justify-end md:col-span-1 md:pt-0.5">
                                <button type="button" class="a-icon-btn" @click="remove(index)" :disabled="items.length === 1" :aria-label="`Remove line ${index + 1}`"><x-icon name="trash" :size="16" /></button>
                            </div>
                        </li>
                    </template>
                </ol>
                <button type="button" class="a-btn a-btn-sm mt-4" @click="add()" :disabled="items.length >= {{ \App\Http\Requests\Admin\InvoiceRequest::MAX_ITEMS }}"><x-icon name="plus" :size="14" /> Add line</button>
                @if ($itemErrors->isNotEmpty())
                    <ul class="mt-3 space-y-1">
                        @foreach ($itemErrors as $message)
                            <li class="a-error !mt-0">{{ $message }}</li>
                        @endforeach
                    </ul>
                @endif

                <div class="mt-6 grid gap-6 border-t border-line pt-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
                    <div class="grid content-start gap-4 sm:grid-cols-3">
                        <x-admin.input name="discount" label="Discount (amount)" :value="$invoice->discount ? \App\Support\Money::toDecimal($invoice->discount) : ''" inputmode="decimal" placeholder="0.00" x-model="discount" />
                        <x-admin.input name="tax_label" label="Tax name" :value="$invoice->tax_label" maxlength="40" placeholder="VAT" />
                        <x-admin.input name="tax_rate" label="Tax rate (%)" :value="rtrim(rtrim((string) $invoice->tax_rate, '0'), '.') ?: '0'" inputmode="decimal" x-model="taxRate" />
                    </div>
                    <dl class="space-y-2 rounded-xl bg-ink-2 p-4 text-sm">
                        <div class="flex justify-between gap-4"><dt class="a-muted">Subtotal</dt><dd class="tabular-nums" x-text="format(subtotal)"></dd></div>
                        <div class="flex justify-between gap-4" x-show="discountMinor > 0"><dt class="a-muted">Discount</dt><dd class="tabular-nums" x-text="'-' + format(discountMinor)"></dd></div>
                        <div class="flex justify-between gap-4" x-show="tax > 0"><dt class="a-muted">Tax</dt><dd class="tabular-nums" x-text="format(tax)"></dd></div>
                        <div class="flex justify-between gap-4 border-t border-line pt-2 text-base font-semibold"><dt>Total</dt><dd class="tabular-nums" x-text="format(total)"></dd></div>
                    </dl>
                </div>
            </div>
        </section>

        <section class="a-card a-card-body grid gap-5 sm:grid-cols-2" aria-label="Shown on the invoice">
            <x-admin.textarea name="payment_instructions" label="Payment instructions" :value="$invoice->payment_instructions" rows="4" hint="Bank or mobile money details. Hidden once the invoice is paid." />
            <x-admin.textarea name="notes" label="Note to client" :value="$invoice->notes" rows="4" />
            <x-admin.textarea name="internal_notes" label="Internal notes" :value="$invoice->internal_notes" rows="3" class="sm:col-span-2" hint="Only visible to your team, never on the invoice." />
        </section>

        @include('admin.partials.save-bar', ['label' => $invoice->exists ? 'Save invoice' : 'Save draft'])
    </form>
@endsection
