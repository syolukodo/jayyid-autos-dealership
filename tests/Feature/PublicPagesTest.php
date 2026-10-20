<?php

namespace Tests\Feature;

use App\Enums\NotificationType;
use App\Enums\UserRole;
use App\Enums\VehicleCategory;
use App\Models\Inquiry;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_static_pages_render(): void
    {
        foreach (['home', 'pages.services', 'pages.about', 'pages.payment-options', 'contact.show', 'login', 'register'] as $route) {
            $this->get(route($route))->assertOk();
        }
    }

    public function test_home_shows_featured_vehicles(): void
    {
        $featured = Vehicle::factory()->featured()->create(['title' => 'Featured Prado']);
        Vehicle::factory()->create(['title' => 'Plain Corolla']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Featured Prado')
            ->assertSee(route('vehicles.show', $featured));
    }

    public function test_inventory_filters_by_body_type_and_budget(): void
    {
        Vehicle::factory()->create(['title' => 'Cheap SUV', 'category' => VehicleCategory::SUV, 'price' => 20_000_000]);
        Vehicle::factory()->create(['title' => 'Pricey SUV', 'category' => VehicleCategory::SUV, 'price' => 70_000_000]);
        Vehicle::factory()->create(['title' => 'Cheap Sedan', 'category' => VehicleCategory::Sedan, 'price' => 20_000_000]);

        $this->get(route('inventory.index', ['body' => 'SUV', 'budget' => '0-25000000']))
            ->assertOk()
            ->assertSee('Cheap SUV')
            ->assertDontSee('Pricey SUV')
            ->assertDontSee('Cheap Sedan');
    }

    public function test_vehicle_page_and_missing_vehicle(): void
    {
        $vehicle = Vehicle::factory()->create(['title' => 'Lexus RX 350']);

        $this->get(route('vehicles.show', $vehicle))->assertOk()->assertSee('Lexus RX 350');
        $this->get('/vehicles/999999')->assertNotFound()->assertSee('This Vehicle Is No Longer Listed');
    }

    public function test_catalogue_api_returns_every_vehicle(): void
    {
        Vehicle::factory()->count(3)->create();

        $this->getJson(route('api.vehicles'))
            ->assertOk()
            ->assertJsonCount(3)
            ->assertJsonStructure([['id', 'title', 'price', 'image', 'url', 'fuel', 'features']]);
    }

    public function test_contact_form_records_an_inquiry_and_alerts_staff(): void
    {
        $admin = $this->staff(UserRole::Admin);
        $vehicle = Vehicle::factory()->create();

        $this->post(route('contact.store'), [
            'full_name' => 'Aisha Bello',
            'phone' => '+234 803 000 1111',
            'email' => 'AISHA@example.com',
            'contact_method' => 'WhatsApp',
            'topic' => 'Payment plan',
            'vehicle' => $vehicle->id,
            'message' => 'Do you offer six months?',
        ])->assertRedirect()->assertSessionHas('inquiry_reference');

        $inquiry = Inquiry::query()->sole();
        $this->assertSame('aisha@example.com', $inquiry->client_email);
        $this->assertSame($vehicle->id, $inquiry->vehicle_id);
        $this->assertSame('WhatsApp', $inquiry->preferred_contact->value);
        $this->assertMatchesRegularExpression('/^JA-INQ-[0-9A-F]{6}$/', $inquiry->reference_code);

        $this->assertDatabaseHas('notifications', ['user_id' => $admin->id, 'type' => NotificationType::Inquiry->value]);
    }

    public function test_contact_form_validates_input(): void
    {
        $this->post(route('contact.store'), ['full_name' => '', 'phone' => 'abc', 'message' => ''])
            ->assertSessionHasErrors(['full_name', 'phone', 'message']);

        $this->assertDatabaseCount('inquiries', 0);
    }

    public function test_vehicle_inquiry_errors_use_the_drawer_error_bag(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->post(route('vehicles.inquiries.store', $vehicle), ['full_name' => 'Musa'])
            ->assertSessionHasErrorsIn('inquiry', ['phone']);
    }

    public function test_sold_vehicles_are_left_out_of_the_inventory_and_its_live_filter(): void
    {
        $available = Vehicle::factory()->create(['title' => 'Available Test Car', 'status' => 'Available']);
        $sold = Vehicle::factory()->create(['title' => 'Sold Test Car', 'status' => 'Sold']);

        $this->get(route('inventory.index'))->assertOk()->assertSee($available->title)->assertDontSee($sold->title);

        $ids = collect($this->getJson(route('api.vehicles'))->json())->pluck('id');
        $this->assertTrue($ids->contains((string) $available->id));
        $this->assertFalse($ids->contains((string) $sold->id));

        // The sold vehicle's own page still resolves for old links and search engines.
        $this->get(route('vehicles.show', $sold))->assertOk();
    }
}
