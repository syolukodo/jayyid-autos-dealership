<?php

namespace App\Enums;

enum InquiryStatus: string
{
    case New = 'New';
    case Contacted = 'Contacted';
    case Resolved = 'Resolved';
    case Closed = 'Closed';

    public function isOpen(): bool
    {
        return $this === self::New || $this === self::Contacted;
    }

    /**
     * Pill styling on the customer portal.
     */
    public function customerPill(): string
    {
        return match ($this) {
            self::New => 'pill pill--wait',
            self::Contacted => 'pill pill--info',
            self::Resolved => 'pill pill--ok',
            self::Closed => 'pill',
        };
    }

    /**
     * Pill styling on the staff lead desk.
     */
    public function staffPill(): string
    {
        return match ($this) {
            self::New => 'pill pill--wait',
            self::Contacted => 'pill pill--info',
            self::Closed => 'pill pill--positive',
            self::Resolved => 'pill',
        };
    }
}
