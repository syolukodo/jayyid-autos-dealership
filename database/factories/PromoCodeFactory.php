<?php

namespace Database\Factories;

use App\Enums\DiscountType;
use App\Enums\PromoTarget;
use App\Models\PromoCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PromoCode>
 */
class PromoCodeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('PROMO##??')),
            'title' => fake()->sentence(3),
            'discount_type' => DiscountType::Percentage,
            'discount_value' => 10,
            'min_order_amount' => 0,
            'target_type' => PromoTarget::All,
            'usage_limit' => null,
            'usage_limit_per_user' => 1,
            'is_active' => true,
        ];
    }

    public function fixed(int $amount): static
    {
        return $this->state(fn () => ['discount_type' => DiscountType::Fixed, 'discount_value' => $amount]);
    }
}
