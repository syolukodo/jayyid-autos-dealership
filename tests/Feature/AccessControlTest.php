<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_sign_in(): void
    {
        $this->get(route('account.dashboard'))->assertRedirect(route('login'));
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_customers_cannot_open_the_back_office(): void
    {
        $this->actingAs($this->customer())
            ->get(route('admin.users.index'))
            ->assertRedirect(route('account.dashboard'));
    }

    public function test_staff_are_kept_out_of_the_customer_portal(): void
    {
        $this->actingAs($this->staff())
            ->get(route('account.dashboard'))
            ->assertRedirect(route('admin.home'));
    }

    public function test_roles_only_reach_their_modules(): void
    {
        $accountant = $this->staff(UserRole::Accountant);

        $this->actingAs($accountant)->get(route('admin.payments.index'))->assertOk();
        $this->actingAs($accountant)->get(route('admin.vehicles.index'))->assertRedirect(route('admin.home'));
        $this->actingAs($accountant)->get(route('admin.home'))->assertRedirect(route('admin.inquiries.index'));
    }

    public function test_every_admin_page_renders_for_an_administrator(): void
    {
        $this->actingAs($this->staff());

        foreach (['admin.dashboard', 'admin.vehicles.index', 'admin.vehicles.create', 'admin.inquiries.index', 'admin.users.index',
            'admin.payments.index', 'admin.promo-codes.index', 'admin.promo-codes.create', 'admin.settings.edit', 'admin.profile.edit'] as $route) {
            $this->get(route($route))->assertOk();
        }
    }

    public function test_every_portal_page_renders_for_a_customer(): void
    {
        $this->actingAs($this->customer());

        foreach (['account.dashboard', 'account.inquiries', 'account.saved.index', 'account.payments', 'account.payment-plans', 'account.settings.edit'] as $route) {
            $this->get(route($route))->assertOk();
        }
    }

    public function test_a_suspension_signs_out_an_active_session(): void
    {
        $user = $this->customer();
        $this->actingAs($user)->get(route('account.dashboard'))->assertOk();

        $user->update(['status' => UserStatus::Suspended]);

        $this->get(route('account.dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
