<?php

namespace App\Services\Delivery;

/**
 * Delivery price for a selection to one state, in whole naira.
 */
final class DeliveryQuote
{
    /**
     * @param  list<array{title: string, amount: int}>  $lines  one per vehicle
     */
    public function __construct(
        public readonly string $state,
        public readonly string $zone,
        public readonly int $fee,
        public readonly array $lines,
    ) {}
}
