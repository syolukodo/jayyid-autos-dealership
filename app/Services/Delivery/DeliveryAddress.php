<?php

namespace App\Services\Delivery;

/**
 * Where the buyer wants the vehicles delivered, as entered at checkout.
 */
final class DeliveryAddress
{
    public function __construct(
        public readonly string $state,
        public readonly string $city,
        public readonly string $address,
    ) {}

    public function label(): string
    {
        return implode(', ', array_filter([$this->address, $this->city, $this->state]));
    }
}
