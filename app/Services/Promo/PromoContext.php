<?php

namespace App\Services\Promo;

use App\Models\Vehicle;
use Illuminate\Support\Collection;

/**
 * The basket and customer a promo code is judged against.
 */
final class PromoContext
{
    /**
     * @param  Collection<int, Vehicle>  $vehicles
     */
    public function __construct(
        public readonly Collection $vehicles,
        public readonly ?int $userId = null,
    ) {}

    public function subtotal(): int
    {
        return (int) $this->vehicles->sum('price');
    }
}
