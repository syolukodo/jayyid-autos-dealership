<?php

namespace App\Services\Payments;

/**
 * A naira charge expressed in the currency PayPal settles in.
 */
final class PayPalAmount
{
    public function __construct(
        public readonly string $currency,
        public readonly int $minor,
        public readonly float $rate,
    ) {}

    /**
     * Decimal string for the Orders API, e.g. "1234.57".
     */
    public function value(): string
    {
        return number_format($this->minor / 100, 2, '.', '');
    }

    public function label(): string
    {
        return currency_label($this->currency, $this->minor);
    }
}
