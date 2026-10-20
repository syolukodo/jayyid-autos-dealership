<?php

namespace App\Services\Promo;

use App\Enums\DiscountType;
use App\Enums\PromoTarget;
use App\Models\Payment;
use App\Models\PromoCode;
use App\Models\Vehicle;

/**
 * Enforces every rule configured in Admin > Promo Codes: active flag, date
 * window, total and per-customer usage limits, minimum order and targeting.
 * Used by the checkout "Apply" button and again when a payment is started.
 */
class PromoCodeEvaluator
{
    public function evaluate(string $code, PromoContext $context): PromoEvaluation
    {
        $code = mb_strtoupper(trim($code));

        if ($code === '') {
            return PromoEvaluation::rejected($code, 'invalid', 'Enter a promo code to apply a discount.');
        }

        $promo = PromoCode::findByCode($code);
        if ($promo === null) {
            return PromoEvaluation::rejected($code, 'not_found', 'That promo code is not valid.');
        }

        $rejection = $this->checkAvailability($promo, $context);
        if ($rejection !== null) {
            return PromoEvaluation::rejected($code, $rejection[0], $rejection[1], $promo);
        }

        $subtotal = $context->subtotal();
        $eligible = $this->eligibleSubtotal($promo, $context);

        if ($promo->target_type !== PromoTarget::All && $eligible <= 0) {
            return PromoEvaluation::rejected($code, 'no_match', 'This promo code does not apply to the vehicles in your selection.', $promo);
        }

        $discount = $this->discountFor($promo, $eligible, $subtotal);
        if ($discount <= 0) {
            return PromoEvaluation::rejected($code, 'no_discount', 'This promo code does not reduce your total.', $promo, $eligible);
        }

        return new PromoEvaluation(
            valid: true,
            reason: 'ok',
            message: $promo->code.' applied - '.naira($discount).' off your selection.',
            code: $promo->code,
            discount: $discount,
            eligible: $eligible,
            promo: $promo,
        );
    }

    /**
     * Record a redemption once the payment that used the code has settled.
     */
    public function redeem(Payment $payment): void
    {
        $promo = $payment->promo_code ? PromoCode::findByCode($payment->promo_code) : null;
        if ($promo === null || ! $payment->user_id || ($payment->promo_discount ?? 0) <= 0) {
            return;
        }

        $promo->redemptions()->create([
            'user_id' => $payment->user_id,
            'order_id' => $payment->id,
            'discount_amount' => $payment->promo_discount,
        ]);
        $promo->increment('times_used');
    }

    /**
     * @return array{0: string, 1: string}|null reason and message, or null when the code may be used
     */
    private function checkAvailability(PromoCode $promo, PromoContext $context): ?array
    {
        if (! $promo->is_active) {
            return ['inactive', 'This promo code is no longer active.'];
        }
        if (! $promo->hasStarted()) {
            return ['not_started', 'This promo code is not active yet.'];
        }
        if ($promo->hasExpired()) {
            return ['expired', 'This promo code has expired.'];
        }
        if ($promo->usage_limit !== null && $promo->redemptions()->count() >= $promo->usage_limit) {
            return ['limit_total', 'This promo code has reached its usage limit.'];
        }
        if ($promo->usage_limit_per_user !== null && $context->userId
            && $promo->redemptions()->where('user_id', $context->userId)->count() >= $promo->usage_limit_per_user) {
            return ['limit_user', 'You have already used this promo code.'];
        }
        if ($promo->min_order_amount > 0 && $context->subtotal() < $promo->min_order_amount) {
            return ['min_order', 'This code applies to orders from '.naira($promo->min_order_amount).'.'];
        }

        return null;
    }

    /**
     * The part of the basket the code may discount: everything for "all",
     * otherwise only the matching vehicles (a Toyota code discounts just the Toyotas).
     */
    private function eligibleSubtotal(PromoCode $promo, PromoContext $context): int
    {
        if ($promo->target_type === PromoTarget::All) {
            return $context->subtotal();
        }

        $vehicleIds = $promo->target_type === PromoTarget::SpecificVehicles
            ? $promo->vehicles()->pluck('vehicles.id')->all()
            : [];

        return (int) $context->vehicles
            ->filter(fn (Vehicle $vehicle) => match ($promo->target_type) {
                PromoTarget::Brand => strcasecmp($vehicle->make, (string) $promo->target_brand) === 0,
                PromoTarget::Category => strcasecmp($vehicle->category->value, (string) $promo->target_category) === 0,
                PromoTarget::SpecificVehicles => in_array($vehicle->id, $vehicleIds, true),
                PromoTarget::All => true,
            })
            ->sum('price');
    }

    private function discountFor(PromoCode $promo, int $eligible, int $subtotal): int
    {
        if ($eligible <= 0) {
            return 0;
        }

        $discount = $promo->discount_type === DiscountType::Percentage
            ? (int) round($eligible * $promo->discount_value / 100)
            : (int) round($promo->discount_value);

        return max(0, min($discount, $eligible, $subtotal));
    }
}
