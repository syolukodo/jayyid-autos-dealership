<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Mail\VerificationCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_signs_in_to_the_portal(): void
    {
        $user = $this->customer(['email' => 'buyer@example.com']);

        $this->post(route('login'), ['email' => 'BUYER@example.com', 'password' => 'password'])
            ->assertRedirect(route('account.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_staff_land_on_the_first_module_their_role_can_open(): void
    {
        $sales = $this->staff(UserRole::SalesOfficer);

        $this->post(route('login'), ['email' => $sales->email, 'password' => 'password'])
            ->assertRedirect(route('admin.home'));

        $this->get(route('admin.home'))->assertRedirect(route('admin.vehicles.index'));
    }

    public function test_wrong_password_and_suspended_accounts_are_rejected(): void
    {
        $user = $this->customer();
        $suspended = User::factory()->suspended()->create();

        $this->post(route('login'), ['email' => $user->email, 'password' => 'nope'])->assertSessionHasErrors('email');
        $this->post(route('login'), ['email' => $suspended->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'This account is suspended. Contact the showroom to restore access.']);

        $this->assertGuest();
    }

    public function test_staff_with_a_pending_invite_cannot_sign_in(): void
    {
        $staff = $this->staff(UserRole::Accountant, ['password' => null]);

        $this->post(route('login'), ['email' => $staff->email, 'password' => ''])->assertSessionHasErrors();
        $this->assertGuest();
    }

    public function test_logout_requires_post_and_returns_customers_home(): void
    {
        $this->actingAs($this->customer());

        $this->get('/logout')->assertStatus(405);
        $this->post(route('logout'))->assertRedirect(route('home', ['signed_out' => 1]));
        $this->assertGuest();
    }

    public function test_registration_without_mail_signs_the_customer_in(): void
    {
        $this->post(route('register'), [
            'name' => 'Zaid Salman',
            'email' => 'zaid@example.com',
            'phone' => '+234 803 456 7890',
            'password' => 'secret-pass',
        ])->assertRedirect(route('account.dashboard'));

        $user = User::query()->where('email', 'zaid@example.com')->sole();
        $this->assertTrue($user->isCustomer());
        $this->assertFalse($user->isVerified());
        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_with_mail_requires_the_emailed_code(): void
    {
        config(['mail.default' => 'smtp']);
        Mail::fake();

        $this->post(route('register'), [
            'name' => 'Zaid Salman',
            'email' => 'zaid@example.com',
            'phone' => '+234 803 456 7890',
            'password' => 'secret-pass',
        ])->assertRedirect(route('verification.show'));

        $this->assertDatabaseMissing('users', ['email' => 'zaid@example.com']);

        $code = null;
        Mail::assertSent(VerificationCode::class, function (VerificationCode $mail) use (&$code) {
            $code = $mail->code;

            return $mail->hasTo('zaid@example.com');
        });

        $this->post(route('verification.verify'), ['code' => '000000'])->assertSessionHasErrors('code');
        $this->post(route('verification.verify'), ['code' => $code])->assertRedirect(route('account.dashboard'));

        $user = User::query()->where('email', 'zaid@example.com')->sole();
        $this->assertTrue($user->isVerified());
        $this->assertAuthenticatedAs($user);
    }

    public function test_duplicate_email_is_rejected(): void
    {
        $this->customer(['email' => 'taken@example.com']);

        $this->post(route('register'), ['name' => 'X', 'email' => 'taken@example.com', 'phone' => '1', 'password' => 'secret-pass'])
            ->assertSessionHasErrors(['email' => 'An account with this email already exists. Please sign in instead.']);
    }

    public function test_checkout_modal_login_rejects_staff_accounts(): void
    {
        $staff = $this->staff();

        $this->postJson(route('api.auth.login'), ['email' => $staff->email, 'password' => 'password'])
            ->assertForbidden()
            ->assertJson(['ok' => false]);

        $this->postJson(route('api.auth.check'), ['email' => $staff->email])->assertJson(['ok' => true, 'exists' => true]);
    }
}
