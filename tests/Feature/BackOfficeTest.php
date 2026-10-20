<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\PromoTarget;
use App\Enums\ScheduleStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Mail\PaymentReceipt;
use App\Models\Payment;
use App\Models\PaymentSchedule;
use App\Models\PromoCode;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackOfficeTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_invite_flow(): void
    {
        $admin = $this->staff();

        $this->actingAs($admin)->post(route('admin.staff.store'), [
            'name' => '  Adeola   Balogun ',
            'email' => 'Adeola@JayyidAutos.ng',
            'phone' => '+234 802 555 0110',
            'role' => 'Sales Officer',
            'status' => 'Active',
        ])->assertRedirect(route('admin.users.index', ['tab' => 'staff']));

        $staff = User::query()->where('email', 'adeola@jayyidautos.ng')->sole();
        $this->assertSame('Adeola Balogun', $staff->full_name);
        $this->assertSame(UserRole::SalesOfficer, $staff->role);
        $this->assertTrue($staff->hasPendingInvite());

        // Mail is not configured in tests, so the one-time link is shown to the admin.
        $link = session('staff_invite')['link'];
        $this->assertNotSame('', $link);

        auth()->logout();
        $this->get($link)->assertOk()->assertSee('Set your password');
        $this->post($link, ['password' => 'new-password', 'password_confirmation' => 'new-password'])->assertRedirect($link);

        $this->assertFalse($staff->refresh()->hasPendingInvite());
        $this->post(route('login'), ['email' => $staff->email, 'password' => 'new-password'])->assertRedirect(route('admin.home'));

        // The link works once.
        auth()->logout();
        $this->get($link)->assertSee('Link expired or already used');
    }

    public function test_the_last_active_administrator_cannot_be_demoted(): void
    {
        $admin = $this->staff();

        $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'account_id' => $admin->id,
            'first_name' => 'Solo',
            'last_name' => 'Admin',
            'email' => $admin->email,
            'role' => 'Sales Officer',
            'status_toggle' => '1',
        ])->assertSessionHasErrorsIn('editAccount', ['form']);

        $this->assertSame(UserRole::Admin, $admin->refresh()->role);
    }

    public function test_administrator_updates_a_customer_account(): void
    {
        $customer = $this->customer();

        $this->actingAs($this->staff())->put(route('admin.users.update', $customer), [
            'account_id' => $customer->id,
            'first_name' => 'Ngozi',
            'last_name' => 'Eze',
            'email' => 'ngozi@example.com',
            'phone' => '',
            'role' => 'Customer',
        ])->assertRedirect(route('admin.users.index'));

        $customer->refresh();
        $this->assertSame('Ngozi Eze', $customer->full_name);
        $this->assertSame(UserStatus::Suspended, $customer->status, 'An unticked status toggle suspends the account.');
    }

    public function test_vehicle_listing_with_photo_upload(): void
    {
        Storage::fake('uploads');

        $this->actingAs($this->staff(UserRole::SalesOfficer))->post(route('admin.vehicles.store'), [
            'title' => '2022 Toyota Land Cruiser Prado TX-L',
            'condition' => 'Tokunbo',
            'category' => 'SUV',
            'make' => 'Toyota',
            'model' => 'Prado',
            'year' => 2022,
            'mileage' => '32,000 km',
            'transmission' => 'Automatic',
            'fuel' => 'Diesel',
            'status' => 'Available',
            'price' => 65000000,
            'description' => 'Clean unit.',
            'featured' => '1',
            // A real 1x1 PNG (UploadedFile::fake()->image() needs the GD extension).
            'image' => UploadedFile::fake()->createWithContent('prado.png', base64_decode(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
            )),
        ])->assertRedirect(route('admin.vehicles.create'));

        $vehicle = Vehicle::query()->sole();
        $this->assertTrue($vehicle->is_featured);
        $this->assertSame('Diesel', $vehicle->fuel_type->value);
        $this->assertStringStartsWith('uploads/vehicles/', $vehicle->primary_image);
        Storage::disk('uploads')->assertExists(substr($vehicle->primary_image, strlen('uploads/')));
    }

    public function test_vehicle_edit_replaces_the_photo(): void
    {
        Storage::fake('uploads');
        Storage::disk('uploads')->put('vehicles/old.png', 'old');
        $vehicle = Vehicle::factory()->create(['primary_image' => 'uploads/vehicles/old.png', 'is_featured' => true]);

        $this->actingAs($this->staff(UserRole::SalesOfficer));
        $this->get(route('admin.vehicles.index'))->assertSee(route('admin.vehicles.edit', $vehicle))->assertSee('data-confirm=', false);
        $this->get(route('admin.vehicles.edit', $vehicle))->assertOk()->assertSee($vehicle->title);

        $this->put(route('admin.vehicles.update', $vehicle), [
            'title' => '2021 Lexus RX 350',
            'condition' => 'Tokunbo',
            'category' => 'SUV',
            'make' => 'Lexus',
            'model' => 'RX 350',
            'year' => 2021,
            'mileage' => '41,000 km',
            'transmission' => 'Automatic',
            'fuel' => 'Petrol',
            'status' => 'Reserved',
            'price' => 48000000,
            'description' => 'Updated notes.',
            'image' => UploadedFile::fake()->createWithContent('rx.png', base64_decode(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
            )),
        ])->assertRedirect(route('admin.vehicles.index'))->assertSessionHas('status');

        $vehicle->refresh();
        $this->assertSame('2021 Lexus RX 350', $vehicle->title);
        $this->assertSame('Reserved', $vehicle->status->value);
        $this->assertFalse($vehicle->is_featured, 'An unticked featured box clears the flag.');
        Storage::disk('uploads')->assertMissing('vehicles/old.png');
        Storage::disk('uploads')->assertExists(substr($vehicle->primary_image, strlen('uploads/')));
    }

    public function test_vehicle_delete_removes_the_listing_and_its_photo(): void
    {
        Storage::fake('uploads');
        Storage::disk('uploads')->put('vehicles/car.png', 'img');
        $vehicle = Vehicle::factory()->create(['primary_image' => 'uploads/vehicles/car.png']);

        $this->actingAs($this->staff(UserRole::SalesOfficer))
            ->delete(route('admin.vehicles.destroy', $vehicle))
            ->assertRedirect(route('admin.vehicles.index'))
            ->assertSessionHas('status');

        $this->assertModelMissing($vehicle);
        Storage::disk('uploads')->assertMissing('vehicles/car.png');
    }

    public function test_vehicle_with_a_payment_plan_cannot_be_deleted(): void
    {
        $vehicle = Vehicle::factory()->create();
        PaymentSchedule::query()->create([
            'schedule_code' => 'JA-PAY-1', 'user_id' => $this->customer()->id, 'vehicle_id' => $vehicle->id,
            'total_price' => 9_000_000, 'deposit' => 3_000_000, 'total_paid' => 3_000_000, 'remaining_balance' => 6_000_000,
            'status' => ScheduleStatus::Active,
        ]);

        $this->actingAs($this->staff())
            ->delete(route('admin.vehicles.destroy', $vehicle))
            ->assertRedirect(route('admin.vehicles.index'))
            ->assertSessionHas('error');

        $this->assertModelExists($vehicle);
    }

    public function test_staff_without_the_vehicles_module_cannot_edit_or_delete(): void
    {
        $vehicle = Vehicle::factory()->create();
        $this->actingAs($this->staff(UserRole::Accountant));

        $this->get(route('admin.vehicles.edit', $vehicle))->assertRedirect(route('admin.home'));
        $this->delete(route('admin.vehicles.destroy', $vehicle))->assertRedirect(route('admin.home'));
        $this->assertModelExists($vehicle);
    }

    public function test_finance_staff_find_and_open_customer_receipts(): void
    {
        $paid = $this->payment(['customer_name' => 'Ada Obi', 'status' => PaymentStatus::Paid, 'paid_at' => now()]);
        $pending = $this->payment(['customer_name' => 'Bayo Ade']);

        $this->actingAs($this->staff(UserRole::Accountant));

        $this->get(route('admin.payments.index'))
            ->assertOk()
            ->assertSee('Customer Payments &amp; Receipts', false)
            ->assertSee(route('admin.payments.receipt', $paid->reference))
            ->assertDontSee(route('admin.payments.receipt', $pending->reference))
            ->assertSee($pending->reference);

        $this->get(route('admin.payments.index', ['q' => 'ada obi']))->assertSee($paid->reference)->assertDontSee($pending->reference);
        $this->get(route('admin.payments.index', ['status' => 'pending']))->assertSee($pending->reference)->assertDontSee($paid->reference);

        $this->get(route('admin.payments.receipt', $paid->reference))
            ->assertOk()
            ->assertSee('Payment Receipt')
            ->assertSee('Ada Obi')
            ->assertSee(route('admin.payments.receipt.resend', $paid->reference));

        // Receipts exist for confirmed payments only.
        $this->get(route('admin.payments.receipt', $pending->reference))->assertNotFound();
    }

    public function test_payment_search_matches_every_word_and_reports_the_count(): void
    {
        $gle = $this->payment(['customer_name' => 'Ibrahim Adebayo', 'vehicle_title' => '2020 Mercedes-Benz GLE 350']);
        $prado = $this->payment(['customer_name' => 'Ibrahim Adebayo', 'vehicle_title' => '2022 Toyota Prado']);
        $other = $this->payment(['customer_name' => 'Ngozi Eze', 'vehicle_title' => '2020 Mercedes-Benz GLE 350']);

        $this->actingAs($this->staff(UserRole::Accountant));

        $this->get(route('admin.payments.index', ['q' => '  ibrahim   gle ']))
            ->assertSee($gle->reference)
            ->assertDontSee($prado->reference)
            ->assertDontSee($other->reference)
            ->assertSee('1 payment found', false);

        $this->get(route('admin.payments.index', ['q' => strtolower(substr($prado->reference, -5))]))
            ->assertSee($prado->reference)
            ->assertDontSee($gle->reference);

        // LIKE wildcards in the search box are literal characters.
        $this->get(route('admin.payments.index', ['q' => '%']))->assertSee('No payments match these filters.');
    }

    public function test_inquiry_filter_offers_every_status(): void
    {
        $this->actingAs($this->staff())->get(route('admin.inquiries.index'))
            ->assertOk()
            ->assertSeeInOrder(['data-filter-value="All"', 'data-filter-value="New"', 'data-filter-value="Contacted"', 'data-filter-value="Resolved"', 'data-filter-value="Closed"'], false);
    }

    public function test_only_staff_with_the_payments_module_can_open_receipts(): void
    {
        $paid = $this->payment(['status' => PaymentStatus::Paid]);

        $this->actingAs($this->staff(UserRole::Admin))->get(route('admin.payments.receipt', $paid->reference))->assertOk();
        $this->actingAs($this->staff(UserRole::SalesOfficer))->get(route('admin.payments.receipt', $paid->reference))->assertRedirect(route('admin.home'));
        $this->actingAs($this->customer())->get(route('admin.payments.receipt', $paid->reference))->assertRedirect(route('account.dashboard'));
    }

    public function test_finance_staff_download_receipt_pdfs(): void
    {
        $paid = $this->payment(['status' => PaymentStatus::Paid, 'paid_at' => now()]);

        $this->actingAs($this->staff(UserRole::Accountant))
            ->get(route('admin.payments.receipt.pdf', $paid->reference))
            ->assertOk()
            ->assertDownload('Receipt-'.$paid->reference.'-Jayyid-Autos.pdf');

        $this->actingAs($this->staff(UserRole::SalesOfficer))
            ->get(route('admin.payments.receipt.pdf', $paid->reference))
            ->assertRedirect(route('admin.home'));
    }

    public function test_staff_resend_a_receipt_email(): void
    {
        Mail::fake();
        $paid = $this->payment(['status' => PaymentStatus::Paid]);
        $this->actingAs($this->staff(UserRole::Accountant));

        config(['mail.default' => 'log']);
        $this->post(route('admin.payments.receipt.resend', $paid->reference))
            ->assertRedirect(route('admin.payments.receipt', $paid->reference))
            ->assertSessionHas('error');
        Mail::assertNothingSent();

        config(['mail.default' => 'smtp']);
        $this->post(route('admin.payments.receipt.resend', $paid->reference))
            ->assertSessionHas('status', 'Receipt emailed to '.$paid->customer_email.'.');
        Mail::assertSent(PaymentReceipt::class, fn (PaymentReceipt $mail) => $mail->hasTo($paid->customer_email) && $mail->payment->is($paid));
    }

    private function payment(array $attributes = []): Payment
    {
        $vehicle = Vehicle::factory()->create();

        return Payment::query()->create($attributes + [
            'reference' => 'JA-PAY-'.strtoupper(bin2hex(random_bytes(6))),
            'purpose' => 'deposit',
            'method' => 'paystack',
            'amount_kobo' => 3_000_000 * 100,
            'vehicle_id' => $vehicle->id,
            'vehicle_title' => $vehicle->title,
            'vehicle_price' => $vehicle->price,
            'customer_name' => 'Chidi Okafor',
            'customer_email' => 'buyer'.random_int(1000, 9999).'@example.test',
        ]);
    }

    public function test_admin_updates_delivery_rates_used_at_checkout(): void
    {
        $this->actingAs($this->staff())
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('Delivery Rates')
            ->assertSee('name="rates[lagos]"', false);

        $factors = ['Sedan' => 100, 'SUV' => 150, 'Pickup' => 125, 'Commercial' => 160];
        $this->put(route('admin.settings.delivery'), [
            'rates' => ['lagos' => 90_000, 'south_west' => 150_000, 'south' => 300_000, 'north_central' => 350_000, 'north' => 500_000],
            'factors' => $factors,
        ])->assertRedirect(route('admin.settings.edit').'#settings-delivery');

        $this->app->forgetScopedInstances();
        $rates = $this->app->make(\App\Services\Delivery\DeliveryRates::class);
        $quote = $rates->quote(collect([Vehicle::factory()->create(['category' => 'SUV'])]), 'Lagos');
        $this->assertSame(135_000, $quote->fee);

        $this->put(route('admin.settings.delivery'), ['rates' => ['lagos' => -5], 'factors' => $factors])
            ->assertSessionHasErrorsIn('delivery');
    }

    public function test_admin_sets_the_paypal_exchange_rate(): void
    {
        config(['services.paypal.currency' => 'USD', 'services.paypal.exchange_rate' => null]);
        $this->actingAs($this->staff());

        $this->get(route('admin.settings.edit'))->assertSee('PayPal Exchange Rate')->assertSee('PayPal is unavailable at checkout');

        $this->put(route('admin.settings.paypal'), ['paypal_exchange_rate' => '1,550.50'])
            ->assertRedirect(route('admin.settings.edit').'#settings-paypal');
        $this->app->forgetScopedInstances();
        $this->assertSame(1550.5, $this->app->make(\App\Services\Payments\PayPalConversion::class)->rate());

        $this->put(route('admin.settings.paypal'), ['paypal_exchange_rate' => '0'])->assertSessionHasErrorsIn('paypal');
    }

    public function test_promo_code_crud(): void
    {
        $this->actingAs($this->staff());
        $vehicle = Vehicle::factory()->create();

        $this->post(route('admin.promo-codes.store'), [
            'code' => 'toyota 10!',
            'title' => 'Toyota offer',
            'discount_type' => 'percentage',
            'discount_value' => '10',
            'target_type' => 'specific_vehicles',
            'vehicle_ids' => [$vehicle->id],
            'is_active' => '1',
        ])->assertRedirect(route('admin.promo-codes.index'));

        $promo = PromoCode::query()->sole();
        $this->assertSame('TOYOTA10', $promo->code);
        $this->assertSame(PromoTarget::SpecificVehicles, $promo->target_type);
        $this->assertSame([$vehicle->id], $promo->vehicles()->pluck('vehicles.id')->all());

        $this->get(route('admin.promo-codes.edit', $promo))->assertOk()->assertSee('Edit Promo Code');

        $this->patch(route('admin.promo-codes.toggle', $promo));
        $this->assertFalse($promo->refresh()->is_active);

        $this->post(route('admin.promo-codes.store'), ['code' => 'TOYOTA10', 'title' => 'Dup', 'discount_type' => 'fixed', 'discount_value' => '5', 'target_type' => 'all'])
            ->assertSessionHasErrors(['code' => 'That promo code already exists.']);

        $this->delete(route('admin.promo-codes.destroy', $promo));
        $this->assertDatabaseCount('promo_codes', 0);
    }

    public function test_settings_change_the_public_business_details(): void
    {
        $this->actingAs($this->staff())->put(route('admin.settings.update'), [
            'business_name' => 'Jayyid Autos',
            'email' => 'hello@jayyid.test',
            'phone' => '0803 111 2222',
            'address' => '1 New Road, Lekki',
            'hours' => 'Daily 8am to 8pm',
            'notify_inquiries' => '1',
        ])->assertRedirect(route('admin.settings.edit'));

        $this->app->forgetScopedInstances();

        $this->get(route('contact.show'))
            ->assertSee('1 New Road, Lekki')
            ->assertSee('hello@jayyid.test')
            ->assertSee('0803 111 2222');

        // A local number becomes an international WhatsApp link.
        $this->assertSame('https://wa.me/2348031112222', app(Business::class)->whatsapp);
    }
}
