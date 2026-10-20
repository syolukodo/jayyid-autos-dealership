<?php

namespace App\Services\Selection;

use App\Services\Delivery\DeliveryQuote;

/**
 * Reservation checkout totals for a vehicle selection, in naira.
 */
final class CheckoutBreakdown
{
    public function __construct(
        public readonly int $count,
        public readonly int $subtotal,
        public readonly int $documentation,
        public readonly int $tax,
        public readonly float $taxRate,
        public readonly int $logistics,
        public readonly int $discount,
        public readonly int $total,
        public readonly bool $delivery,
        public readonly ?DeliveryQuote $deliveryQuote,
        public readonly string $promoCode,
        public readonly string $promoMessage,
    ) {}
}
