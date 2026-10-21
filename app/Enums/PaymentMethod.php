<?php

namespace App\Enums;

enum PaymentMethod: string
{
    use Concerns;

    case BankTransfer = 'bank_transfer';
    case MobileMoney = 'mobile_money';
    case Card = 'card';
    case Cash = 'cash';
    case Cheque = 'cheque';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::BankTransfer => 'Bank transfer',
            self::MobileMoney => 'Mobile money',
            self::Card => 'Card',
            self::Cash => 'Cash',
            self::Cheque => 'Cheque',
            self::Other => 'Other',
        };
    }
}
