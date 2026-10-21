<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Models\Concerns\TracksEditors;
use App\Services\SiteSettings;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A customer we invoice. Each invoice keeps a copy of these billing details
 * from when it was issued, so editing a client never changes past invoices.
 */
#[Fillable(['name', 'company', 'email', 'phone', 'address', 'tax_number', 'currency', 'notes'])]
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory, TracksEditors;

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($term): void {
            foreach (['name', 'company', 'email', 'phone'] as $column) {
                $query->orWhere($column, 'like', '%'.$term.'%');
            }
        });
    }

    /**
     * The name shown in lists: the organisation when there is one, else the contact.
     */
    public function displayName(): string
    {
        return $this->company ?: $this->name;
    }

    public function defaultCurrency(): string
    {
        return strtoupper($this->currency ?: (string) app(SiteSettings::class)->get('invoicing.currency', 'GHS'));
    }

    /**
     * The billing details printed on an invoice.
     *
     * @return array{name: string, company: ?string, email: ?string, phone: ?string, address: ?string, tax_number: ?string}
     */
    public function billingDetails(): array
    {
        return [
            'name' => $this->name,
            'company' => $this->company,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'tax_number' => $this->tax_number,
        ];
    }

    /**
     * Invoiced, paid and outstanding totals per currency (void and draft invoices excluded).
     *
     * @return array<string, array{invoiced: int, paid: int, outstanding: int}>
     */
    public function balances(): array
    {
        return $this->invoices()
            ->whereNotIn('status', [InvoiceStatus::Draft->value, InvoiceStatus::Void->value])
            ->selectRaw('currency, sum(total) as invoiced, sum(amount_paid) as paid')
            ->groupBy('currency')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->currency => [
                'invoiced' => (int) $row->invoiced,
                'paid' => (int) $row->paid,
                'outstanding' => max(0, (int) $row->invoiced - (int) $row->paid),
            ]])
            ->all();
    }
}
