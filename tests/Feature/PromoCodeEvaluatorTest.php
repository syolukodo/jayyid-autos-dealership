<?php

namespace Tests\Feature;

use App\Enums\PromoTarget;
use App\Enums\VehicleCategory;
use App\Models\PromoCode;
use App\Models\Vehicle;
use App\Services\Promo\PromoCodeEvaluator;
use App\Services\Promo\PromoContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromoCodeEvaluatorTest extends TestCase
{
    use RefreshDatabase;

    private PromoCodeEvaluator $evaluator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->evaluator = new PromoCodeEvaluator;
    }

    public function test_percentage_code_discounts_the_whole_basket(): void
    {
        PromoCode::factory()->create(['code' => 'TEN']);
        $basket = Vehicle::factory()->count(2)->create(['price' => 10_000_000]);

        $result = $this->evaluator->evaluate(' ten ', new PromoContext($basket));

        $this->assertTrue($result->valid);
        $this->assertSame(2_000_000, $result->discount);
    }

    public function test_brand_code_only_discounts_matching_vehicles(): void
    {
        PromoCode::factory()->create(['code' => 'TOYOTA10', 'target_type' => PromoTarget::Brand, 'target_brand' => 'Toyota']);
        $basket = collect([
            Vehicle::factory()->create(['make' => 'Toyota', 'price' => 10_000_000]),
            Vehicle::factory()->create(['make' => 'Kia', 'price' => 10_000_000]),
        ]);

        $this->assertSame(1_000_000, $this->evaluator->evaluate('TOYOTA10', new PromoContext($basket))->discount);

        $kiaOnly = new PromoContext($basket->where('make', 'Kia')->values());
        $this->assertSame('no_match', $this->evaluator->evaluate('TOYOTA10', $kiaOnly)->reason);
    }

    public function test_specific_vehicle_and_category_targeting(): void
    {
        $suv = Vehicle::factory()->create(['category' => VehicleCategory::SUV, 'price' => 20_000_000]);
        $sedan = Vehicle::factory()->create(['category' => VehicleCategory::Sedan, 'price' => 10_000_000]);
        PromoCode::factory()->fixed(1_000_000)->create(['code' => 'SUVONLY', 'target_type' => PromoTarget::Category, 'target_category' => 'SUV']);
        PromoCode::factory()->create(['code' => 'PICKED', 'target_type' => PromoTarget::SpecificVehicles])->vehicles()->attach($sedan);

        $basket = new PromoContext(collect([$suv, $sedan]));
        $this->assertSame(1_000_000, $this->evaluator->evaluate('SUVONLY', $basket)->discount);
        $this->assertSame(1_000_000, $this->evaluator->evaluate('PICKED', $basket)->discount);
    }

    public function test_fixed_discount_never_exceeds_the_eligible_amount(): void
    {
        PromoCode::factory()->fixed(50_000_000)->create(['code' => 'HUGE']);
        $basket = Vehicle::factory()->count(1)->create(['price' => 10_000_000]);

        $this->assertSame(10_000_000, $this->evaluator->evaluate('HUGE', new PromoContext($basket))->discount);
    }

    public function test_availability_rules(): void
    {
        $basket = Vehicle::factory()->count(1)->create(['price' => 10_000_000]);
        $context = new PromoContext($basket, userId: null);

        PromoCode::factory()->create(['code' => 'OFF', 'is_active' => false]);
        PromoCode::factory()->create(['code' => 'LATER', 'start_date' => now()->addDay()]);
        PromoCode::factory()->create(['code' => 'OLD', 'expiry_date' => now()->subDay()]);
        PromoCode::factory()->create(['code' => 'BIG', 'min_order_amount' => 50_000_000]);

        $this->assertSame('not_found', $this->evaluator->evaluate('NOPE', $context)->reason);
        $this->assertSame('inactive', $this->evaluator->evaluate('OFF', $context)->reason);
        $this->assertSame('not_started', $this->evaluator->evaluate('LATER', $context)->reason);
        $this->assertSame('expired', $this->evaluator->evaluate('OLD', $context)->reason);
        $this->assertSame('min_order', $this->evaluator->evaluate('BIG', $context)->reason);
    }

    public function test_usage_limits(): void
    {
        $customer = $this->customer();
        $basket = Vehicle::factory()->count(1)->create();

        $perUser = PromoCode::factory()->create(['code' => 'ONCE', 'usage_limit_per_user' => 1]);
        $perUser->redemptions()->create(['user_id' => $customer->id, 'discount_amount' => 1]);
        $this->assertSame('limit_user', $this->evaluator->evaluate('ONCE', new PromoContext($basket, $customer->id))->reason);

        $total = PromoCode::factory()->create(['code' => 'FIRST1', 'usage_limit' => 1]);
        $total->redemptions()->create(['user_id' => $this->customer()->id, 'discount_amount' => 1]);
        $this->assertSame('limit_total', $this->evaluator->evaluate('FIRST1', new PromoContext($basket, $customer->id))->reason);
    }
}
