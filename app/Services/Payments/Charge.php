<?php

namespace App\Services\Payments;

use App\Enums\PaymentPurpose;
use App\Services\Delivery\DeliveryAddress;

/**
 * What a payment will charge, derived on the server from catalogue,
 * schedule or selection data. The browser never chooses the amount.
 */
final class Charge
{
    public function __construct(
        public readonly PaymentPurpose $purpose,
        public readonly int $amount,
        public readonly ?int $vehicleId,
        public readonly string $vehicleTitle,
        public readonly int $vehiclePrice,
        public readonly ?int $outstanding = null,
        public readonly ?int $scheduleId = null,
        public readonly string $promoCode = '',
        public readonly int $promoDiscount = 0,
        public readonly ?DeliveryAddress $delivery = null,
        public readonly int $deliveryFee = 0,
        /** @var list<int> vehicles a selection payment reserves */
        public readonly array $vehicleIds = [],
    ) {}
}
