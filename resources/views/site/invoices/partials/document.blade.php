{{-- The invoice as the client sees it. Needs $invoice (with client, items, payments) and $issuer. --}}
@php
    $billTo = $invoice->billTo();
    $stamp = match (true) {
        $invoice->isVoid() => ['VOID', true],
        $invoice->isPaid() => ['PAID', false],
        $invoice->isOverdue() => ['OVERDUE', true],
        $invoice->isDraft() => ['DRAFT', true],
        default => null,
    };
@endphp
<article class="inv-doc" aria-label="Invoice {{ $invoice->number }}">
    @if ($stamp)
        <p class="inv-stamp {{ $stamp[1] ? 'inv-stamp-danger' : '' }}">{{ $stamp[0] }}</p>
    @endif

    <header class="inv-head {{ $stamp ? 'inv-head-with-stamp' : '' }}">
        <div>
            <span class="inv-logo"><x-site.logo :label="$issuer['name']" /></span>
            <div class="inv-from">
                <strong>{{ $issuer['name'] }}</strong>
                @if ($issuer['address'])<p class="inv-pre inv-muted">{{ $issuer['address'] }}</p>@endif
                @if ($issuer['email'])<p class="inv-muted">{{ $issuer['email'] }}</p>@endif
                @if ($issuer['phone'])<p class="inv-muted">{{ $issuer['phone'] }}</p>@endif
                @if ($issuer['tax_number'])<p class="inv-muted">Tax ID: {{ $issuer['tax_number'] }}</p>@endif
            </div>
        </div>
        <div class="inv-title">
            <h1>Invoice</h1>
            <p>{{ $invoice->number }}</p>
        </div>
    </header>

    <section class="inv-meta">
        <div class="inv-billto">
            <p class="inv-label">Bill to</p>
            <strong>{{ $billTo['company'] ?: $billTo['name'] }}</strong>
            @if ($billTo['company'] && $billTo['name'])<p>Attn: {{ $billTo['name'] }}</p>@endif
            @if ($billTo['address'])<p class="inv-pre inv-muted">{{ $billTo['address'] }}</p>@endif
            @if ($billTo['email'])<p class="inv-muted">{{ $billTo['email'] }}</p>@endif
            @if ($billTo['phone'])<p class="inv-muted">{{ $billTo['phone'] }}</p>@endif
            @if ($billTo['tax_number'])<p class="inv-muted">Tax ID: {{ $billTo['tax_number'] }}</p>@endif
        </div>
        <div>
            <dl>
                <dt>Invoice date</dt>
                <dd>{{ $invoice->issue_date->format('j M Y') }}</dd>
                @if ($invoice->due_date)
                    <dt>Due date</dt>
                    <dd>{{ $invoice->due_date->format('j M Y') }}</dd>
                @endif
                <dt>Currency</dt>
                <dd>{{ $invoice->currency }}</dd>
            </dl>
            @unless ($invoice->isVoid())
                <p class="inv-due">
                    <span>{{ $invoice->isPaid() ? 'Paid in full' : 'Amount due' }}</span>
                    <span class="inv-num">{{ $invoice->money($invoice->isPaid() ? $invoice->total : $invoice->balance()) }}</span>
                </p>
            @endunless
        </div>
    </section>

    @if ($invoice->subject)
        <p class="inv-subject">{{ $invoice->subject }}</p>
    @endif

    <table class="inv-table">
        <thead>
            <tr>
                <th scope="col" class="inv-label">Description</th>
                <th scope="col" class="inv-label inv-num inv-hide-sm">Qty</th>
                <th scope="col" class="inv-label inv-num inv-hide-sm">Unit price</th>
                <th scope="col" class="inv-label inv-num">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $item)
                <tr>
                    <td class="inv-pre">{{ $item->description }}</td>
                    <td class="inv-num inv-hide-sm">{{ $item->quantityLabel() }}</td>
                    <td class="inv-num inv-hide-sm">{{ number_format($item->unit_price / 100, 2) }}</td>
                    <td class="inv-num">{{ number_format($item->amount / 100, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="inv-totals">
        <dl>
            <dt>Subtotal</dt>
            <dd>{{ $invoice->money($invoice->subtotal) }}</dd>
            @if ($invoice->discount > 0)
                <dt>Discount</dt>
                <dd>-{{ $invoice->money($invoice->discount) }}</dd>
            @endif
            @if ((float) $invoice->tax_rate > 0)
                <dt>{{ $invoice->tax_label ?: 'Tax' }} ({{ rtrim(rtrim((string) $invoice->tax_rate, '0'), '.') }}%)</dt>
                <dd>{{ $invoice->money($invoice->tax_amount) }}</dd>
            @endif
            <dt class="inv-total">Total</dt>
            <dd class="inv-total">{{ $invoice->money($invoice->total) }}</dd>
            @if ($invoice->amount_paid > 0)
                <dt>Paid</dt>
                <dd>-{{ $invoice->money($invoice->amount_paid) }}</dd>
                <dt class="inv-balance">Balance due</dt>
                <dd class="inv-balance">{{ $invoice->money($invoice->balance()) }}</dd>
            @endif
        </dl>
    </div>

    @if ($invoice->payments->isNotEmpty())
        <section class="inv-payments">
            <p class="inv-label">Payments received</p>
            <table class="inv-table">
                <tbody>
                    @foreach ($invoice->payments as $payment)
                        <tr>
                            <td>{{ $payment->paid_on->format('j M Y') }}</td>
                            <td class="inv-muted">{{ $payment->method->label() }}{{ $payment->reference ? ' · '.$payment->reference : '' }}</td>
                            <td class="inv-num">{{ $invoice->money($payment->amount) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif

    @if ($invoice->payment_instructions || $invoice->notes)
        <section class="inv-notes">
            @if ($invoice->payment_instructions && ! $invoice->isPaid() && ! $invoice->isVoid())
                <div>
                    <p class="inv-label">How to pay</p>
                    <p class="inv-pre">{{ $invoice->payment_instructions }}</p>
                    <p class="inv-muted" style="margin-top: 0.5rem">Please use <strong>{{ $invoice->number }}</strong> as your payment reference.</p>
                </div>
            @endif
            @if ($invoice->notes)
                <div>
                    <p class="inv-label">Notes</p>
                    <p class="inv-pre">{{ $invoice->notes }}</p>
                </div>
            @endif
        </section>
    @endif

    <p class="inv-foot">{{ $issuer['name'] }}{{ $issuer['email'] ? ' · '.$issuer['email'] : '' }}{{ $issuer['phone'] ? ' · '.$issuer['phone'] : '' }}</p>
</article>
