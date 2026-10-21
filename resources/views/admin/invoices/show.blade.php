@extends('layouts.admin')

@section('title', 'Invoice '.$invoice->number)

@php
    $link = $invoice->shareUrl();
    $billTo = $invoice->billTo();
    $firstName = \Illuminate\Support\Str::before((string) $billTo['name'], ' ');
    $shareText = "Hi {$firstName},\n\nHere is invoice {$invoice->number}".($invoice->subject ? " for {$invoice->subject}" : '').': '.$invoice->money($invoice->balance()).' due'.($invoice->due_date ? ' by '.$invoice->due_date->format('j F Y') : '').".\n\nYou can view, print or download it here:\n{$link}\n\nThank you,\n{$issuer['name']}";
    $paymentErrors = $errors->getBag('payment');
@endphp

@section('content')
    <x-admin.page-header :title="'Invoice '.$invoice->number" :description="collect([$invoice->client->displayName(), $invoice->subject])->filter()->implode(' · ')" :back="route('admin.clients.show', $invoice->client)">
        <x-slot:actions>
            <a href="{{ route('admin.invoices.preview', $invoice) }}" target="_blank" rel="noopener" class="a-btn"><x-icon name="eye" :size="16" /> Preview & print</a>
            @can('invoices.manage')
                @unless ($invoice->isVoid())
                    <a href="{{ route('admin.invoices.edit', $invoice) }}" class="a-btn"><x-icon name="edit" :size="16" /> Edit</a>
                @endunless
                <form method="POST" action="{{ route('admin.invoices.duplicate', $invoice) }}">
                    @csrf
                    <button type="submit" class="a-btn"><x-icon name="copy" :size="16" /> Duplicate</button>
                </form>
                @if ($invoice->isDraft() && $invoice->payments->isEmpty())
                    <x-admin.delete-button :action="route('admin.invoices.destroy', $invoice)" confirm="Delete this draft invoice permanently?" size="md" />
                @elseif ($invoice->isVoid())
                    <form method="POST" action="{{ route('admin.invoices.restore', $invoice) }}">
                        @csrf
                        <button type="submit" class="a-btn"><x-icon name="history" :size="16" /> Restore</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.invoices.void', $invoice) }}" data-confirm="Void this invoice? It stays on record, but it no longer counts as owed and the client sees it as cancelled.">
                        @csrf
                        <button type="submit" class="a-btn a-btn-danger"><x-icon name="close" :size="16" /> Void</button>
                    </form>
                @endif
            @endcan
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="min-w-0 space-y-6">
            @can('invoices.manage')
                @unless ($invoice->isVoid())
                    <section class="a-card" aria-labelledby="send-heading">
                        <div class="a-card-header">
                            <h2 id="send-heading" class="a-card-title">{{ $invoice->isDraft() ? 'Send to your client' : 'Share with your client' }}</h2>
                            @if ($invoice->viewed_at)
                                <span class="text-xs a-subtle">Client opened it {{ $invoice->viewed_at->diffForHumans() }}</span>
                            @elseif ($invoice->sent_at)
                                <span class="text-xs a-subtle">Not opened yet</span>
                            @endif
                        </div>
                        <div class="a-card-body space-y-4">
                            @if ($invoice->isDraft())
                                <p class="text-sm a-muted">This is a draft, so the link below does not work yet. Email it to your client, or mark it as sent if you are sharing it another way.</p>
                            @endif

                            <form method="POST" action="{{ route('admin.invoices.email', $invoice) }}" class="flex flex-col gap-2 sm:flex-row sm:items-start" data-confirm="Email this invoice to the address shown?">
                                @csrf
                                <div class="min-w-0 flex-1">
                                    <label for="send-email" class="sr-only">Client email</label>
                                    <input id="send-email" type="email" name="email" value="{{ old('email', $invoice->recipientEmail()) }}" required placeholder="client@example.com" class="a-input" @error('email') aria-invalid="true" @enderror>
                                    @error('email')<p class="a-error">{{ $message }}</p>@enderror
                                </div>
                                <button type="submit" class="a-btn a-btn-primary flex-none"><x-icon name="send" :size="16" /> {{ $invoice->sent_at ? 'Email again' : 'Email invoice' }}</button>
                            </form>

                            <div class="flex flex-col gap-2 sm:flex-row">
                                <label for="share-link" class="sr-only">Invoice link</label>
                                <input id="share-link" type="text" readonly value="{{ $link }}" class="a-input font-mono text-xs" @focus="$el.select()">
                                <button type="button" class="a-btn flex-none" data-copy="{{ $link }}"><x-icon name="copy" :size="16" /> Copy link</button>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                @if ($invoice->isDraft())
                                    <form method="POST" action="{{ route('admin.invoices.send', $invoice) }}">
                                        @csrf
                                        <button type="submit" class="a-btn a-btn-sm"><x-icon name="check" :size="15" /> Mark as sent</button>
                                    </form>
                                @endif
                                <a href="https://wa.me/?text={{ rawurlencode($shareText) }}" target="_blank" rel="noopener noreferrer" class="a-btn a-btn-sm"><x-icon name="whatsapp" :size="15" /> Share on WhatsApp</a>
                                <form method="POST" action="{{ route('admin.invoices.regenerate', $invoice) }}" data-confirm="Generate a new link? The current link will stop working immediately.">
                                    @csrf
                                    <button type="submit" class="a-btn a-btn-sm a-btn-ghost"><x-icon name="lock" :size="15" /> Generate a new link</button>
                                </form>
                            </div>
                            <p class="text-xs a-subtle">Anyone with the link can view this invoice, so share it only with your client. Emails go out within a minute or two.</p>
                        </div>
                    </section>
                @endunless
            @endcan

            <div class="overflow-x-auto rounded-2xl bg-paper-2 p-3 sm:p-6">
                @include('site.invoices.partials.styles')
                @include('site.invoices.partials.document')
            </div>
        </div>

        <aside class="space-y-6">
            <section class="a-card a-card-body space-y-4" aria-labelledby="status-heading">
                <div class="flex items-center justify-between gap-3">
                    <h2 id="status-heading" class="a-card-title">Status</h2>
                    <div class="flex gap-1">
                        <x-admin.status-badge :status="$invoice->status" />
                        @if ($invoice->isOverdue())
                            <span class="a-badge a-badge-danger">Overdue</span>
                        @endif
                    </div>
                </div>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-4"><dt class="a-subtle">Total</dt><dd class="tabular-nums">{{ $invoice->money($invoice->total) }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="a-subtle">Paid</dt><dd class="tabular-nums">{{ $invoice->money($invoice->amount_paid) }}</dd></div>
                    <div class="flex justify-between gap-4 border-t border-line pt-2 text-base font-semibold"><dt>Balance</dt><dd class="tabular-nums {{ $invoice->balance() > 0 && ! $invoice->isVoid() ? 'text-lime' : '' }}">{{ $invoice->isVoid() ? '—' : $invoice->money($invoice->balance()) }}</dd></div>
                </dl>
                <dl class="space-y-2.5 border-t border-line pt-4 text-sm">
                    @foreach ([
                        'Invoice date' => $invoice->issue_date?->format('j M Y'),
                        'Due' => $invoice->due_date?->format('j M Y') ?? 'On receipt',
                        'Sent' => $invoice->sent_at?->format('j M Y, H:i'),
                        'Opened by client' => $invoice->viewed_at?->format('j M Y, H:i'),
                        'Paid in full' => $invoice->paid_at?->format('j M Y'),
                        'Voided' => $invoice->voided_at?->format('j M Y, H:i'),
                        'Created by' => $invoice->creator?->name,
                    ] as $label => $value)
                        @if ($value)
                            <div class="flex justify-between gap-4"><dt class="a-subtle">{{ $label }}</dt><dd class="text-right">{{ $value }}</dd></div>
                        @endif
                    @endforeach
                </dl>
            </section>

            <section class="a-card" aria-labelledby="payments-heading">
                <div class="a-card-header"><h2 id="payments-heading" class="a-card-title">Payments</h2></div>
                @if ($invoice->payments->isEmpty())
                    <p class="a-card-body text-sm a-muted">No payments recorded yet.</p>
                @else
                    <ul class="divide-y divide-line text-sm">
                        @foreach ($invoice->payments as $payment)
                            <li class="flex items-start gap-3 px-5 py-3">
                                <div class="min-w-0 flex-1">
                                    <p class="font-semibold tabular-nums">{{ $invoice->money($payment->amount) }}</p>
                                    <p class="text-xs a-subtle">{{ $payment->paid_on->format('j M Y') }} · {{ $payment->method->label() }}{{ $payment->reference ? ' · '.$payment->reference : '' }}</p>
                                    @if ($payment->note)
                                        <p class="mt-1 text-xs a-muted">{{ $payment->note }}</p>
                                    @endif
                                </div>
                                @can('invoices.manage')
                                    <form method="POST" action="{{ route('admin.invoices.payments.destroy', [$invoice, $payment]) }}" data-confirm="Remove this payment?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="a-icon-btn !size-8" aria-label="Remove payment of {{ $invoice->money($payment->amount) }}"><x-icon name="trash" :size="15" /></button>
                                    </form>
                                @endcan
                            </li>
                        @endforeach
                    </ul>
                @endif

                @can('invoices.manage')
                    @if (! $invoice->isVoid() && $invoice->balance() > 0)
                        <form method="POST" action="{{ route('admin.invoices.payments.store', $invoice) }}" class="space-y-4 border-t border-line p-5" x-data="{ open: {{ $paymentErrors->any() ? 'true' : 'false' }} }">
                            @csrf
                            <button type="button" class="a-btn a-btn-sm w-full" x-show="! open" @click="open = true; $nextTick(() => $refs.amount.focus())"><x-icon name="plus" :size="14" /> Record a payment</button>
                            <div class="space-y-4" x-show="open" x-cloak>
                                <h3 class="font-semibold">Record a payment</h3>
                                @foreach ($paymentErrors->all() as $message)
                                    <p class="a-error !mt-0">{{ $message }}</p>
                                @endforeach
                                <div>
                                    <label for="payment-amount" class="a-label">Amount ({{ $invoice->currency }})</label>
                                    <input id="payment-amount" x-ref="amount" type="text" inputmode="decimal" name="amount" required class="a-input tabular-nums" value="{{ old('amount', \App\Support\Money::toDecimal($invoice->balance())) }}">
                                </div>
                                <div>
                                    <label for="payment-date" class="a-label">Date received</label>
                                    <input id="payment-date" type="date" name="paid_on" required class="a-input" value="{{ old('paid_on', today()->format('Y-m-d')) }}" max="{{ today()->format('Y-m-d') }}">
                                </div>
                                <div>
                                    <label for="payment-method" class="a-label">Method</label>
                                    <select id="payment-method" name="method" class="a-input">
                                        @foreach ($methods as $value => $label)
                                            <option value="{{ $value }}" @selected(old('method', 'bank_transfer') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="payment-reference" class="a-label">Reference (optional)</label>
                                    <input id="payment-reference" type="text" name="reference" maxlength="120" class="a-input" value="{{ old('reference') }}" placeholder="Transaction ID">
                                </div>
                                <div>
                                    <label for="payment-note" class="a-label">Note (optional)</label>
                                    <textarea id="payment-note" name="note" rows="2" maxlength="1000" class="a-input">{{ old('note') }}</textarea>
                                </div>
                                <div class="flex gap-2">
                                    <button type="submit" class="a-btn a-btn-primary">Save payment</button>
                                    <button type="button" class="a-btn a-btn-ghost" @click="open = false">Cancel</button>
                                </div>
                            </div>
                        </form>
                    @endif
                @endcan
            </section>

            @if ($invoice->internal_notes)
                <section class="a-card a-card-body">
                    <h2 class="a-card-title">Internal notes</h2>
                    <p class="mt-3 whitespace-pre-line text-sm">{{ $invoice->internal_notes }}</p>
                </section>
            @endif
        </aside>
    </div>
@endsection
