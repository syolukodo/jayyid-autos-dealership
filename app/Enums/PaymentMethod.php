<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Paystack = 'paystack';
    case Transfer = 'transfer';
    case PayPal = 'paypal';

    public function label(): string
    {
        return match ($this) {
            self::Paystack => 'Paystack Card',
            self::Transfer => 'Bank Transfer',
            self::PayPal => 'PayPal',
        };
    }
}
