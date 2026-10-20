<?php

namespace App\Services\Promo;

use App\Models\PromoCode;

/**
 * Outcome of checking a promo code: the naira discount plus a message to show either way.
 */
final class PromoEvaluation
{
    public function __construct(
        public readonly bool $valid,
        public readonly string $reason,
        public readonly string $message,
        public readonly string $code,
        public readonly int $discount = 0,
        public readonly int $eligible = 0,
        public readonly ?PromoCode $promo = null,
    ) {}

    public static function rejected(string $code, string $reason, string $message, ?PromoCode $promo = null, int $eligible = 0): self
    {
        return new self(false, $reason, $message, $code, 0, $eligible, $promo);
    }
}
