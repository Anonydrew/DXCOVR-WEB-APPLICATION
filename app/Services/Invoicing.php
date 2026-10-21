<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Notifications\InvoiceIssued;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Invoice numbering, totals, payments and status changes.
 */
class Invoicing
{
    public function __construct(protected SiteSettings $settings, protected AuditLogger $audit) {}

    /**
     * The business details printed at the top of every invoice. Falls back
     * to the contact details in Settings when no invoice-specific value is set.
     *
     * @return array{name: string, address: ?string, email: ?string, phone: ?string, tax_number: ?string}
     */
    public function issuer(): array
    {
        $settings = $this->settings;

        return [
            'name' => (string) ($settings->get('invoicing.business_name') ?: $settings->get('seo.organization_legal_name') ?: $settings->get('site.name')),
            'address' => $settings->get('invoicing.address') ?: $settings->get('contact.address'),
            'email' => $settings->get('invoicing.email') ?: $settings->get('contact.email'),
            'phone' => $settings->get('invoicing.phone') ?: $settings->get('contact.phone'),
            'tax_number' => $settings->get('invoicing.tax_number'),
        ];
    }

    /**
     * The next free number for the year, e.g. "INV-2026-0007".
     */
    public function nextNumber(?int $year = null): string
    {
        $prefix = strtoupper((string) $this->settings->get('invoicing.number_prefix', 'INV'));
        $start = $prefix.'-'.($year ?? now()->year).'-';

        $highest = Invoice::query()
            ->where('number', 'like', $start.'%')
            ->pluck('number')
            ->map(fn (string $number) => (int) Str::after($number, $start))
            ->max() ?? 0;

        return $start.str_pad((string) ($highest + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * A new, unsaved invoice for the client with the default settings filled in.
     */
    public function draftFor(?Client $client): Invoice
    {
        $invoice = new Invoice([
            'number' => $this->nextNumber(),
            'issue_date' => today(),
            'due_date' => today()->addDays((int) $this->settings->get('invoicing.payment_days', 14)),
            'currency' => $client?->defaultCurrency() ?? strtoupper((string) $this->settings->get('invoicing.currency', 'GHS')),
            'tax_label' => $this->settings->get('invoicing.tax_label'),
            'tax_rate' => (float) $this->settings->get('invoicing.tax_rate', 0),
            'payment_instructions' => $this->settings->get('invoicing.payment_instructions'),
            'notes' => $this->settings->get('invoicing.footer'),
            'discount' => 0,
        ]);

        if ($client) {
            $invoice->client_id = $client->id;
            $invoice->setRelation('client', $client);
        }

        return $invoice;
    }

    /**
     * Saves the invoice and replaces its line items, recalculating every total.
     *
     * @param  array<string, mixed>  $attributes
     * @param  list<array{description: string, quantity: float|string, unit_price: int}>  $items
     */
    public function save(Invoice $invoice, array $attributes, array $items): Invoice
    {
        return DB::transaction(function () use ($invoice, $attributes, $items): Invoice {
            $invoice->fill($attributes);
            $invoice->bill_to = $invoice->client()->first()?->billingDetails();
            $invoice->save();

            $invoice->items()->delete();

            foreach (array_values($items) as $position => $item) {
                $quantity = round((float) $item['quantity'], 2);

                $invoice->items()->create([
                    'position' => $position,
                    'description' => $item['description'],
                    'quantity' => $quantity,
                    'unit_price' => $item['unit_price'],
                    'amount' => (int) round($quantity * $item['unit_price']),
                ]);
            }

            $this->recalculate($invoice);

            return $invoice;
        });
    }

    /**
     * Recomputes the totals from the stored lines and payments, then the status.
     */
    public function recalculate(Invoice $invoice): void
    {
        $subtotal = (int) $invoice->items()->sum('amount');
        $discount = min(max(0, (int) $invoice->discount), $subtotal);
        $tax = (int) round(($subtotal - $discount) * (float) $invoice->tax_rate / 100);

        $invoice->subtotal = $subtotal;
        $invoice->discount = $discount;
        $invoice->tax_amount = $tax;
        $invoice->total = $subtotal - $discount + $tax;
        $invoice->amount_paid = (int) $invoice->payments()->sum('amount');

        $this->refreshStatus($invoice);
        $invoice->save();
    }

    protected function refreshStatus(Invoice $invoice): void
    {
        if ($invoice->voided_at !== null) {
            $invoice->status = InvoiceStatus::Void;

            return;
        }

        if ($invoice->amount_paid > 0 && $invoice->amount_paid >= $invoice->total) {
            $invoice->status = InvoiceStatus::Paid;
            $lastPayment = $invoice->payments()->max('paid_on');
            $invoice->paid_at = $lastPayment ? Carbon::parse($lastPayment)->startOfDay() : ($invoice->paid_at ?? now());

            return;
        }

        $invoice->paid_at = null;

        if ($invoice->amount_paid > 0) {
            $invoice->status = InvoiceStatus::PartiallyPaid;
        } else {
            $invoice->status = $invoice->sent_at ? InvoiceStatus::Sent : InvoiceStatus::Draft;
        }
    }

    /**
     * Marks the invoice as issued, keeping a copy of the client's current billing details.
     */
    public function markSent(Invoice $invoice): void
    {
        if ($invoice->sent_at !== null) {
            return;
        }

        $invoice->sent_at = now();
        $invoice->bill_to = $invoice->client?->billingDetails() ?? $invoice->bill_to;
        $this->refreshStatus($invoice);
        $invoice->save();

        $this->audit->record('invoice.sent', $invoice, 'Marked invoice '.$invoice->number.' as sent');
    }

    /**
     * Emails the client a link to the invoice and marks it as sent.
     */
    public function email(Invoice $invoice, string $email): void
    {
        $this->markSent($invoice);

        Notification::route('mail', $email)->notify(new InvoiceIssued($invoice));

        $this->audit->record('invoice.emailed', $invoice, 'Emailed invoice '.$invoice->number.' to '.$email);
    }

    /**
     * @param  array{amount: int, paid_on: string, method: string, reference?: ?string, note?: ?string}  $data
     */
    public function recordPayment(Invoice $invoice, array $data): InvoicePayment
    {
        return DB::transaction(function () use ($invoice, $data): InvoicePayment {
            $payment = $invoice->payments()->create($data);

            $invoice->sent_at ??= now();
            $this->recalculate($invoice);

            $this->audit->record('invoice.payment_recorded', $invoice, 'Recorded a payment of '.$invoice->money($payment->amount).' on invoice '.$invoice->number);

            return $payment;
        });
    }

    public function deletePayment(Invoice $invoice, InvoicePayment $payment): void
    {
        DB::transaction(function () use ($invoice, $payment): void {
            $payment->delete();
            $this->recalculate($invoice);
        });

        $this->audit->record('invoice.payment_deleted', $invoice, 'Removed a payment of '.$invoice->money($payment->amount).' from invoice '.$invoice->number);
    }

    public function void(Invoice $invoice): void
    {
        $invoice->voided_at = now();
        $this->refreshStatus($invoice);
        $invoice->save();

        $this->audit->record('invoice.voided', $invoice, 'Voided invoice '.$invoice->number);
    }

    public function restore(Invoice $invoice): void
    {
        $invoice->voided_at = null;
        $this->refreshStatus($invoice);
        $invoice->save();

        $this->audit->record('invoice.restored', $invoice, 'Restored voided invoice '.$invoice->number);
    }

    /**
     * Copies an invoice into a new draft with today's dates and the next number.
     */
    public function duplicate(Invoice $source): Invoice
    {
        $copy = $this->draftFor($source->client);

        $this->save($copy, [
            'subject' => $source->subject,
            'currency' => $source->currency,
            'discount' => $source->discount,
            'tax_label' => $source->tax_label,
            'tax_rate' => $source->tax_rate,
            'notes' => $source->notes,
            'payment_instructions' => $source->payment_instructions,
        ], $source->items->map(fn ($item) => [
            'description' => $item->description,
            'quantity' => $item->quantity,
            'unit_price' => $item->unit_price,
        ])->all());

        $this->audit->record('invoice.created', $copy, 'Created invoice '.$copy->number.' as a copy of '.$source->number);

        return $copy;
    }
}
