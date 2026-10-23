<?php

namespace Tests\Feature;

use App\Models\PaymentSchedule;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Renders every page against the demo seed data, so view code is exercised
 * with realistic records (schedules, instalments, inquiries, promo codes).
 */
class SeededDemoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed inside the test transaction ($seed only applies when migrations run).
        $this->seed();
    }

    public function test_seed_loads_the_catalogue_and_demo_accounts(): void
    {
        $this->assertGreaterThan(10, Vehicle::query()->count());
        $this->assertTrue(User::query()->where('email', 'admin@jayyidautos.com')->sole()->isStaff());
        $this->assertNotNull(User::query()->where('email', 'buyer@jayyidautos.com')->sole()->currentSchedule());
        $this->assertGreaterThan(0, PaymentSchedule::query()->count());
    }

    public function test_demo_accounts_sign_in_with_the_documented_password(): void
    {
        $this->post(route('login'), ['email' => 'buyer@jayyidautos.com', 'password' => 'jayyid2026'])
            ->assertRedirect(route('account.dashboard'));
    }

    public function test_public_pages_render_with_demo_data(): void
    {
        $vehicle = Vehicle::query()->first();

        foreach ([route('home'), route('inventory.index'), route('inventory.index', ['page' => 2]), route('vehicles.show', $vehicle), route('contact.show')] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_customer_portal_renders_for_the_demo_buyer(): void
    {
        $buyer = User::query()->where('email', 'buyer@jayyidautos.com')->sole();
        $vehicle = Vehicle::query()->where('status', 'Available')->first();
        $this->actingAs($buyer);

        $this->postJson(route('api.selection.store'), ['vehicle_id' => $vehicle->id])->assertOk();

        foreach ([route('account.dashboard'), route('account.inquiries'), route('account.saved.index'), route('account.payments'),
            route('account.payment-plans', $vehicle), route('account.checkout', $vehicle), route('account.settings.edit')] as $url) {
            $this->get($url)->assertOk();
        }

        $this->get(route('account.payments'))->assertSee('Instalment Schedule')->assertSee($vehicle->title);
    }

    public function test_back_office_renders_for_the_demo_admin(): void
    {
        $this->actingAs(User::query()->where('email', 'admin@jayyidautos.com')->sole());

        foreach (['admin.dashboard', 'admin.vehicles.index', 'admin.inquiries.index', 'admin.users.index', 'admin.payments.index', 'admin.promo-codes.index', 'admin.settings.edit'] as $route) {
            $this->get(route($route))->assertOk();
        }

        $this->get(route('admin.promo-codes.index'))->assertSee('JAYYID10');
    }
}
