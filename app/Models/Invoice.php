<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Models\Concerns\TracksEditors;
use App\Support\Money;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * An invoice for one client. Totals are stored in minor units and always
 * recalculated on the server from the line items (see App\Services\Invoicing).
 *
 * Clients view it through an unguessable link. Only a SHA-256 hash of the
 * token is used for look-ups; the token itself is stored encrypted so staff
 * can copy the link again.
 */
#[Fillable(['client_id', 'number', 'subject', 'issue_date', 'due_date', 'currency', 'discount', 'tax_label', 'tax_rate', 'notes', 'payment_instructions', 'internal_notes'])]
#[Hidden(['token', 'token_hash'])]
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory, TracksEditors;

    public const TOKEN_LENGTH = 48;

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'issue_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'integer',
            'discount' => 'integer',
            'tax_rate' => 'decimal:3',
            'tax_amount' => 'integer',
            'total' => 'integer',
            'amount_paid' => 'integer',
            'bill_to' => 'array',
            'token' => 'encrypted',
            'sent_at' => 'datetime',
            'viewed_at' => 'datetime',
            'paid_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $invoice): void {
            $invoice->status ??= InvoiceStatus::Draft;

            if ($invoice->token_hash === null) {
                $invoice->issueToken();
            }
        });
    }

    /**
     * Generates a new link token, invalidating any previous link. Returns the plain token.
     */
    public function issueToken(): string
    {
        $token = Str::random(self::TOKEN_LENGTH);
        $this->token = $token;
        $this->token_hash = hash('sha256', $token);

        return $token;
    }

    public static function findByToken(string $token): ?self
    {
        if (strlen($token) !== self::TOKEN_LENGTH) {
            return null;
        }

        return static::query()->where('token_hash', hash('sha256', $token))->first();
    }

    public function shareUrl(): string
    {
        return route('invoices.show', $this->token);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('position')->orderBy('id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class)->orderBy('paid_on')->orderBy('id');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereIn('status', InvoiceStatus::open())->whereNotNull('due_date')->whereDate('due_date', '<', today());
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($term): void {
            $query->where('number', 'like', '%'.$term.'%')
                ->orWhere('subject', 'like', '%'.$term.'%')
                ->orWhereHas('client', fn (Builder $client) => $client->search($term));
        });
    }

    public function isDraft(): bool
    {
        return $this->status === InvoiceStatus::Draft;
    }

    public function isVoid(): bool
    {
        return $this->status === InvoiceStatus::Void;
    }

    public function isPaid(): bool
    {
        return $this->status === InvoiceStatus::Paid;
    }

    public function isOverdue(): bool
    {
        return in_array($this->status?->value, InvoiceStatus::open(), true)
            && $this->due_date !== null
            && $this->due_date->lt(today());
    }

    public function balance(): int
    {
        return max(0, $this->total - $this->amount_paid);
    }

    public function money(int $minor): string
    {
        return Money::format($minor, $this->currency);
    }

    /**
     * Who the invoice is addressed to: the copy saved with the invoice, else the client's current details.
     *
     * @return array{name: string, company: ?string, email: ?string, phone: ?string, address: ?string, tax_number: ?string}
     */
    public function billTo(): array
    {
        return $this->bill_to ?: ($this->client?->billingDetails() ?? ['name' => '', 'company' => null, 'email' => null, 'phone' => null, 'address' => null, 'tax_number' => null]);
    }

    /**
     * The email address to send the invoice to.
     */
    public function recipientEmail(): ?string
    {
        return $this->client?->email ?: ($this->billTo()['email'] ?? null);
    }
}
