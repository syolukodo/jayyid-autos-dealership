<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    // Charged, but not the amount or currency recorded: needs manual review.
    case Mismatch = 'mismatch';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Paid => 'Paid',
            self::Failed => 'Failed',
            self::Mismatch => 'Needs review',
        };
    }

    public function pillClass(): string
    {
        return match ($this) {
            self::Paid => 'pill pill--positive',
            self::Pending => 'pill pill--wait',
            self::Failed, self::Mismatch => 'pill pill--danger',
        };
    }
}
