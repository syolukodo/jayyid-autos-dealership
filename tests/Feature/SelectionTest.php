<?php

namespace Tests\Feature;

use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitors_add_remove_and_clear_vehicles(): void
    {
        [$a, $b] = Vehicle::factory()->count(2)->create();

        $this->postJson(route('api.selection.store'), ['vehicle_id' => $a->id])->assertOk()->assertJson(['count' => 1]);
        $this->postJson(route('api.selection.store'), ['vehicle_id' => $b->id])->assertJson(['count' => 2]);
        // Adding the same vehicle twice keeps a single entry.
        $this->postJson(route('api.selection.store'), ['vehicle_id' => $a->id])->assertJson(['count' => 2]);

        $this->getJson(route('api.selection.index'))
            ->assertJson(['count' => 2, 'subtotal' => $a->price + $b->price])
            ->assertJsonPath('items.0.id', (string) $a->id);

        $this->deleteJson(route('api.selection.destroy', $a->id))->assertJson(['count' => 1]);
        $this->deleteJson(route('api.selection.clear'))->assertJson(['count' => 0]);
    }

    public function test_unknown_vehicles_are_rejected(): void
    {
        $this->postJson(route('api.selection.store'), ['vehicle_id' => 9999])->assertNotFound()->assertJson(['ok' => false]);
    }

    public function test_deleted_vehicles_drop_out_of_the_selection(): void
    {
        $vehicle = Vehicle::factory()->create();
        $this->postJson(route('api.selection.store'), ['vehicle_id' => $vehicle->id]);

        $vehicle->delete();
        // A real next request gets fresh per-request services; the test client reuses the container.
        $this->app->forgetScopedInstances();

        $this->getJson(route('api.selection.index'))->assertJson(['count' => 0]);
    }

    public function test_save_buttons_know_who_is_signed_in(): void
    {
        $this->get(route('home'))->assertSee('data-auth="0"', false);
        $this->actingAs($this->customer())->get(route('home'))->assertSee('data-auth="1"', false);
        // Staff get a message instead of being bounced to sign-in (and on into the back office).
        $this->actingAs($this->staff())->get(route('home'))->assertSee('data-auth="staff"', false);
    }

    public function test_saved_vehicles_belong_to_the_signed_in_customer(): void
    {
        $vehicle = Vehicle::factory()->create();
        $this->putJson(route('api.saved.store', $vehicle))->assertUnauthorized();

        $customer = $this->customer();
        $this->actingAs($customer)->putJson(route('api.saved.store', $vehicle))->assertJson(['ok' => true, 'saved' => true]);
        $this->assertTrue($customer->savedVehicles()->whereKey($vehicle->id)->exists());

        $this->deleteJson(route('api.saved.destroy', $vehicle))->assertJson(['saved' => false]);
        $this->assertFalse($customer->savedVehicles()->exists());
    }
}
