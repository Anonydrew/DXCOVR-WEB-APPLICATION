<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    use Concerns;

    case Draft = 'draft';
    case Sent = 'sent';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Void = 'void';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Sent => 'Sent',
            self::PartiallyPaid => 'Partly paid',
            self::Paid => 'Paid',
            self::Void => 'Void',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'muted',
            self::Sent => 'info',
            self::PartiallyPaid => 'warning',
            self::Paid => 'success',
            self::Void => 'danger',
        };
    }

    /**
     * Statuses where the client still owes money.
     *
     * @return list<string>
     */
    public static function open(): array
    {
        return [self::Sent->value, self::PartiallyPaid->value];
    }
}
