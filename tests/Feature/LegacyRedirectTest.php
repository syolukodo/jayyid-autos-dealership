<?php

namespace Tests\Feature;

use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_old_php_urls_redirect_permanently(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->get('/inventory.php?make=Toyota')->assertRedirect(route('inventory.index', ['make' => 'Toyota']))->assertStatus(301);
        $this->get('/vehicle-details.php?id='.$vehicle->id)->assertRedirect(route('vehicles.show', $vehicle));
        $this->get('/user/login.php')->assertRedirect(route('login'));
        $this->get('/admin/staff-setup.php?token='.str_repeat('a', 64))->assertRedirect(route('staff.invitation.show', str_repeat('a', 64)));
    }

    public function test_in_flight_paypal_returns_keep_their_query_string(): void
    {
        $this->get('/user/payments.php?paypal=1&reference=JA-PAY-ABC&token=ORDER1')
            ->assertRedirect(route('account.payments', ['paypal' => 1, 'reference' => 'JA-PAY-ABC', 'token' => 'ORDER1']));
    }

    public function test_unknown_php_paths_are_not_found(): void
    {
        $this->get('/wp-login.php')->assertNotFound();
        $this->get('/vehicle-details.php?id=abc')->assertNotFound();
    }
}
