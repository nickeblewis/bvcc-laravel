<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentMethod: string implements HasLabel
{
    case BankTransfer = 'bank_transfer';
    case Cash = 'cash';
    case PayPal = 'paypal';
    case Card = 'card';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::BankTransfer => 'Bank transfer',
            self::Cash => 'Cash',
            self::PayPal => 'PayPal',
            self::Card => 'Card',
            self::Other => 'Other',
        };
    }
}
