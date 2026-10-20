<?php

namespace App\Enums;

/**
 * What a checkout payment pays for. The amount is always derived on the server.
 */
enum PaymentPurpose: string
{
    // 30% of the catalogue price, reserving a single vehicle.
    case Deposit = 'deposit';
    // The next scheduled instalment plus the documentation fee.
    case Instalment = 'instalment';
    // The full remaining balance of the customer's schedule.
    case Balance = 'balance';
    // The reservation total for the session's vehicle selection.
    case Cart = 'cart';

    public function label(): string
    {
        return match ($this) {
            self::Deposit => 'Reservation deposit',
            self::Instalment => 'Instalment payment',
            self::Balance => 'Balance settlement',
            self::Cart => 'Vehicle reservation',
        };
    }

    public function isScheduled(): bool
    {
        return $this === self::Instalment || $this === self::Balance;
    }
}
