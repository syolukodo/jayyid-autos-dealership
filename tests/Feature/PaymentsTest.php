<?php

namespace Tests\Feature;

use App\Enums\InstalmentStatus;
use App\Enums\PaymentStatus;
use App\Enums\ScheduleStatus;
use App\Enums\VehicleStatus;
use App\Mail\PaymentReceipt;
use App\Models\Payment;
use App\Models\PaymentSchedule;
use App\Models\PromoCode;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PaymentsTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'sk_test_secret';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.paystack.secret_key' => self::SECRET, 'services.paystack.currency' => 'NGN']);
    }

    public function test_online_payment_is_unavailable_without_gateway_keys(): void
    {
        config(['services.paystack.secret_key' => null]);

        $this->actingAs($this->customer())
            ->postJson(route('api.payments.store'), ['purpose' => 'cart'])
            ->assertStatus(503);
    }

    public function test_cart_payment_amount_is_derived_on_the_server(): void
    {
        Http::fake(['api.paystack.co/transaction/initialize' => Http::response(['status' => true, 'message' => 'ok', 'data' => ['access_code' => 'AC_123']])]);

        $customer = $this->customer();
        $vehicle = Vehicle::factory()->create(['price' => 10_000_000, 'category' => 'Sedan']);
        $this->actingAs($customer)->postJson(route('api.selection.store'), ['vehicle_id' => $vehicle->id]);

        $this->postJson(route('api.payments.store'), [
            'purpose' => 'cart', 'method' => 'paystack', 'amount' => 1,
            'delivery' => 'delivery', 'delivery_state' => 'Lagos', 'delivery_city' => 'Ikeja', 'delivery_address' => '12 Allen Avenue',
        ])
            ->assertOk()
            ->assertJson(['ok' => true, 'method' => 'paystack', 'access_code' => 'AC_123']);

        // 10,000,000 + 250,000 documentation + 750,000 tax + 75,000 Lagos sedan delivery
        $payment = Payment::query()->sole();
        $this->assertSame(11_075_000 * 100, $payment->amount_kobo);
        $this->assertSame(PaymentStatus::Pending, $payment->status);
        $this->assertSame(75_000, $payment->delivery_fee);
        $this->assertSame('12 Allen Avenue, Ikeja, Lagos', $payment->deliveryDestination());

        Http::assertSent(fn ($request) => $request['amount'] === 11_075_000 * 100
            && $request['reference'] === $payment->reference
            && $request->hasHeader('Authorization', 'Bearer '.self::SECRET));
    }

    public function test_delivery_is_priced_by_state_zone_and_vehicle_size(): void
    {
        Http::fake(['api.paystack.co/transaction/initialize' => Http::response(['status' => true, 'message' => 'ok', 'data' => ['access_code' => 'AC']])]);

        $customer = $this->customer();
        $suv = Vehicle::factory()->create(['price' => 20_000_000, 'category' => 'SUV']);
        $bus = Vehicle::factory()->create(['price' => 30_000_000, 'category' => 'Commercial']);
        $this->actingAs($customer)->postJson(route('api.selection.store'), ['vehicle_id' => $suv->id]);
        $this->postJson(route('api.selection.store'), ['vehicle_id' => $bus->id]);

        $this->postJson(route('api.payments.store'), [
            'purpose' => 'cart', 'delivery' => 'delivery', 'delivery_state' => 'Kano', 'delivery_address' => '4 Zoo Road',
        ])->assertOk();

        // Kano is in the North zone (500,000): SUV at 125% + bus at 160%.
        $payment = Payment::query()->sole();
        $this->assertSame(625_000 + 800_000, $payment->delivery_fee);
        $this->assertSame((50_000_000 + 250_000 + 3_750_000 + 1_425_000) * 100, $payment->amount_kobo);
    }

    public function test_delivery_needs_a_served_state_and_an_address(): void
    {
        $customer = $this->customer();
        $vehicle = Vehicle::factory()->create();
        $this->actingAs($customer)->postJson(route('api.selection.store'), ['vehicle_id' => $vehicle->id]);

        $this->postJson(route('api.payments.store'), ['purpose' => 'cart', 'delivery' => 'delivery', 'delivery_address' => '1 Road'])
            ->assertStatus(422)->assertJson(['message' => 'Choose the state we should deliver to.']);
        $this->postJson(route('api.payments.store'), ['purpose' => 'cart', 'delivery' => 'delivery', 'delivery_state' => 'Atlantis', 'delivery_address' => '1 Road'])
            ->assertStatus(422);
        $this->postJson(route('api.payments.store'), ['purpose' => 'cart', 'delivery' => 'delivery', 'delivery_state' => 'Lagos'])
            ->assertStatus(422)->assertJson(['message' => 'Enter the street address for doorstep delivery.']);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_delivery_shows_on_the_receipt(): void
    {
        $customer = $this->customer();
        $payment = Payment::query()->create([
            'reference' => 'JA-PAY-DELIVERY1', 'user_id' => $customer->id, 'purpose' => 'cart', 'method' => 'paystack',
            'amount_kobo' => (10_000_000 + 250_000 + 750_000 + 150_000) * 100, 'vehicle_title' => '1 selected vehicle', 'vehicle_price' => 10_000_000,
            'customer_name' => $customer->full_name, 'customer_email' => $customer->email, 'status' => PaymentStatus::Paid, 'paid_at' => now(),
            'delivery_fee' => 150_000, 'delivery_state' => 'Ogun', 'delivery_city' => 'Abeokuta', 'delivery_address' => '9 Oke Ilewo',
        ]);

        $this->actingAs($customer)->get(route('account.payments.receipt', $payment->reference))
            ->assertOk()
            ->assertSee('Delivery to 9 Oke Ilewo, Abeokuta, Ogun')
            ->assertSeeInOrder(['Documentation &amp; tax', naira(1_000_000), 'Doorstep delivery to Ogun', naira(150_000)], false);
    }

    public function test_checkout_offers_every_state_with_live_rates(): void
    {
        $customer = $this->customer();
        $vehicle = Vehicle::factory()->create(['category' => 'Pickup']);
        $this->actingAs($customer)->postJson(route('api.selection.store'), ['vehicle_id' => $vehicle->id]);

        $response = $this->get(route('account.reservation'))->assertOk()
            ->assertSee('<option value="FCT - Abuja">', false)
            ->assertSee('From '.naira(75_000))
            ->assertSee('data-vehicle-categories=\'["Pickup"]\'', false);

        $this->assertSame(37, substr_count($response->getContent(), '<option value="') - 1);
    }

    public function test_reserved_or_sold_vehicles_cannot_be_added_to_a_selection(): void
    {
        $this->actingAs($this->customer());
        $available = Vehicle::factory()->create();
        $this->postJson(route('api.selection.store'), ['vehicle_id' => $available->id])->assertOk();

        foreach ([VehicleStatus::Reserved, VehicleStatus::Sold] as $status) {
            $taken = Vehicle::factory()->create(['status' => $status]);
            $this->postJson(route('api.selection.store'), ['vehicle_id' => $taken->id])
                ->assertStatus(409)
                ->assertJson(['ok' => false, 'message' => 'Sorry, the '.$taken->title.' is no longer available.', 'count' => 1])
                ->assertJsonPath('items.0.id', (string) $available->id);
        }

        // Its own button is disabled (other "similar vehicle" cards on the page keep theirs).
        $reserved = Vehicle::query()->where('status', 'Reserved')->sole();
        $html = $this->get(route('vehicles.show', $reserved))->assertOk()->assertSee('is-unavailable', false)->getContent();
        $this->assertDoesNotMatchRegularExpression('/data-selection-add\s+data-vehicle-id="'.$reserved->id.'"/', $html);
    }

    public function test_a_vehicle_reserved_after_selection_blocks_checkout_payment(): void
    {
        $customer = $this->customer();
        $keep = Vehicle::factory()->create(['price' => 10_000_000, 'title' => 'Keep Me Sedan']);
        $gone = Vehicle::factory()->create(['price' => 20_000_000, 'title' => 'Gone Already SUV']);
        $this->actingAs($customer)->postJson(route('api.selection.store'), ['vehicle_id' => $keep->id]);
        $this->postJson(route('api.selection.store'), ['vehicle_id' => $gone->id]);

        // Another buyer reserves it; a real request would load the selection afresh.
        $gone->update(['status' => VehicleStatus::Reserved]);
        $this->app->forgetScopedInstances();

        $this->get(route('account.reservation'))
            ->assertOk()
            ->assertSee('One vehicle in your selection is')
            ->assertSee('data-unavailable="1"', false)
            ->assertSee('data-subtotal="10000000"', false);
        $this->getJson(route('api.selection.index'))
            ->assertJson(['count' => 2, 'subtotal' => 10_000_000])
            ->assertJsonPath('items.1.available', false);

        $this->postJson(route('api.payments.store'), ['purpose' => 'cart'])
            ->assertStatus(409)
            ->assertJson(['message' => 'Gone Already SUV is no longer available. Remove it from your selection to continue.']);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_a_paid_selection_reserves_its_vehicles(): void
    {
        Http::fake(['api.paystack.co/transaction/initialize' => Http::response(['status' => true, 'message' => 'ok', 'data' => ['access_code' => 'AC']])]);
        $customer = $this->customer();
        [$one, $two] = Vehicle::factory()->count(2)->create(['price' => 10_000_000]);
        $this->actingAs($customer)->postJson(route('api.selection.store'), ['vehicle_id' => $one->id]);
        $this->postJson(route('api.selection.store'), ['vehicle_id' => $two->id]);

        $this->postJson(route('api.payments.store'), ['purpose' => 'cart'])->assertOk();
        $payment = Payment::query()->sole();
        $this->assertSame([$one->id, $two->id], $payment->vehicle_ids);

        $this->fakeVerify($payment, 'success');
        $this->getJson(route('api.payments.show', $payment->reference))->assertJson(['status' => 'paid']);

        $this->assertSame(VehicleStatus::Reserved, $one->refresh()->status);
        $this->assertSame(VehicleStatus::Reserved, $two->refresh()->status);
    }

    public function test_two_buyers_paying_for_the_same_car_sends_the_second_to_review(): void
    {
        config(['mail.default' => 'smtp']);
        Mail::fake();
        Http::fake(['api.paystack.co/transaction/initialize' => Http::response(['status' => true, 'message' => 'ok', 'data' => ['access_code' => 'AC']])]);
        $staff = $this->staff();
        $customer = $this->customer();
        $contested = Vehicle::factory()->create(['price' => 10_000_000, 'title' => 'Contested Camry']);
        $other = Vehicle::factory()->create(['price' => 12_000_000]);
        PromoCode::factory()->fixed(100_000)->create(['code' => 'SAVE100']);

        $this->actingAs($customer)->postJson(route('api.selection.store'), ['vehicle_id' => $contested->id]);
        $this->postJson(route('api.selection.store'), ['vehicle_id' => $other->id]);
        $this->postJson(route('api.payments.store'), ['purpose' => 'cart', 'promo' => 'SAVE100'])->assertOk();
        $payment = Payment::query()->sole();

        // Another buyer's deposit lands first.
        $contested->update(['status' => VehicleStatus::Reserved]);

        $this->fakeVerify($payment, 'success');
        $this->getJson(route('api.payments.show', $payment->reference))->assertJson(['status' => 'mismatch']);

        $payment->refresh();
        $this->assertSame(PaymentStatus::Mismatch, $payment->status);
        $this->assertStringContainsString('Contested Camry', $payment->gateway_message);
        $this->assertSame(VehicleStatus::Reserved, $other->refresh()->status, 'The rest of the order is held for the buyer.');
        $this->assertDatabaseCount('promo_code_redemptions', 0);
        Mail::assertNothingSent();
        $this->assertSame(1, $staff->portalNotifications()->where('title', 'Payment needs review: vehicle already reserved')->count());
        $this->assertSame(1, $customer->portalNotifications()->where('title', 'Payment received - our team will contact you')->count());

        // Re-verifying (webhook, refresh) does not flip it to paid or notify again.
        $this->getJson(route('api.payments.show', $payment->reference))->assertJson(['status' => 'mismatch']);
        $this->assertSame(1, $staff->portalNotifications()->count());

        $this->get(route('account.reservation', ['reference' => $payment->reference]))
            ->assertSee('Your payment needs a manual review');
    }

    public function test_a_deposit_for_a_car_reserved_meanwhile_goes_to_review(): void
    {
        $customer = $this->customer();
        $vehicle = Vehicle::factory()->create(['price' => 10_000_000]);
        $payment = $this->pendingPayment($customer, $vehicle, 'deposit', 3_000_000);
        $vehicle->update(['status' => VehicleStatus::Reserved]);

        $this->fakeVerify($payment, 'success');
        $this->actingAs($customer)->getJson(route('api.payments.show', $payment->reference))->assertJson(['status' => 'mismatch']);
        $this->get(route('account.payments.receipt', $payment->reference))->assertNotFound();
    }

    public function test_paypal_charges_are_converted_from_naira_and_verified_in_that_currency(): void
    {
        config([
            'services.paystack.secret_key' => null,
            'services.paypal' => ['mode' => 'sandbox', 'client_id' => 'id', 'secret' => 'secret', 'currency' => 'USD', 'exchange_rate' => '1550', 'webhook_id' => null],
        ]);
        Http::fake([
            '*/v1/oauth2/token' => Http::response(['access_token' => 'TOKEN']),
            '*/v2/checkout/orders' => Http::response(['id' => 'ORDER-1', 'links' => [['rel' => 'approve', 'href' => 'https://paypal.test/approve']]], 201),
            '*/v2/checkout/orders/ORDER-1/capture' => Http::response([
                'status' => 'COMPLETED',
                'purchase_units' => [['payments' => ['captures' => [[
                    'status' => 'COMPLETED', 'amount' => ['currency_code' => 'USD', 'value' => '645.17'], 'create_time' => now()->toIso8601String(),
                ]]]]],
            ]),
        ]);

        $customer = $this->customer();
        $vehicle = Vehicle::factory()->create(['price' => 10_000_000]);
        $schedule = PaymentSchedule::query()->create([
            'schedule_code' => 'JA-PAY-PP', 'user_id' => $customer->id, 'vehicle_id' => $vehicle->id,
            'total_price' => 10_000_000, 'deposit' => 9_000_000, 'total_paid' => 9_000_000, 'remaining_balance' => 1_000_000,
            'status' => ScheduleStatus::Active,
        ]);
        $schedule->instalments()->create(['sequence' => 1, 'due_date' => now(), 'amount' => 1_000_000, 'status' => 'Due']);

        // N1,000,000 / 1550 = $645.161... -> rounded up to $645.17.
        $this->actingAs($customer)->postJson(route('api.payments.store'), ['purpose' => 'balance', 'vehicle_id' => $vehicle->id, 'method' => 'paypal'])
            ->assertOk()
            ->assertJson(['method' => 'paypal', 'charged' => '$645.17']);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/v2/checkout/orders')
            && $request['purchase_units'][0]['amount'] === ['currency_code' => 'USD', 'value' => '645.17']);

        $payment = Payment::query()->sole();
        $this->assertSame(1_000_000 * 100, $payment->amount_kobo, 'The ledger stays in naira.');
        $this->assertSame(['USD', 64517, 1550.0], [$payment->gateway_currency, $payment->gateway_amount_minor, $payment->exchange_rate]);

        $this->get(route('account.payments', ['paypal' => 1, 'reference' => $payment->reference, 'token' => 'ORDER-1']))->assertOk();
        $this->assertSame(PaymentStatus::Paid, $payment->refresh()->status);

        $this->get(route('account.payments.receipt', $payment->reference))
            ->assertSee('Charged by PayPal')
            ->assertSee('$645.17 USD');
    }

    public function test_checkout_shows_the_paypal_currency_and_rate(): void
    {
        config(['services.paypal' => ['mode' => 'sandbox', 'client_id' => 'id', 'secret' => 'secret', 'currency' => 'USD', 'exchange_rate' => '1550', 'webhook_id' => null]]);
        $customer = $this->customer();
        $vehicle = Vehicle::factory()->create();
        $this->actingAs($customer)->postJson(route('api.selection.store'), ['vehicle_id' => $vehicle->id]);

        $this->get(route('account.reservation'))
            ->assertOk()
            ->assertSee('data-paypal-currency="USD"', false)
            ->assertSee('data-paypal-rate="1550"', false)
            ->assertSee('PayPal charges in USD at &#8358;1,550.00 = $1.00', false);
    }

    public function test_paypal_is_unavailable_without_an_exchange_rate(): void
    {
        config(['services.paypal' => ['mode' => 'sandbox', 'client_id' => 'id', 'secret' => 'secret', 'currency' => 'USD', 'exchange_rate' => null, 'webhook_id' => null]]);
        Http::fake();
        $customer = $this->customer();
        $vehicle = Vehicle::factory()->create();
        $this->actingAs($customer)->postJson(route('api.selection.store'), ['vehicle_id' => $vehicle->id]);

        $this->postJson(route('api.payments.store'), ['purpose' => 'cart', 'method' => 'paypal'])
            ->assertStatus(503)
            ->assertJson(['message' => 'PayPal is not available right now. Please pay by card or bank transfer.']);
        $this->assertDatabaseCount('payments', 0);
        Http::assertNothingSent();
    }

    public function test_a_paypal_capture_for_a_different_amount_is_flagged(): void
    {
        config(['services.paypal' => ['mode' => 'sandbox', 'client_id' => 'id', 'secret' => 'secret', 'currency' => 'USD', 'exchange_rate' => '1550', 'webhook_id' => null]]);
        Http::fake([
            '*/v1/oauth2/token' => Http::response(['access_token' => 'TOKEN']),
            '*/v2/checkout/orders/ORDER-2/capture' => Http::response([
                'status' => 'COMPLETED',
                'purchase_units' => [['payments' => ['captures' => [['status' => 'COMPLETED', 'amount' => ['currency_code' => 'USD', 'value' => '1.00']]]]]],
            ]),
        ]);
        $customer = $this->customer();
        $vehicle = Vehicle::factory()->create();
        $payment = $this->pendingPayment($customer, $vehicle, 'deposit', 3_000_000);
        $payment->update(['method' => 'paypal', 'gateway_reference' => 'ORDER-2', 'gateway_currency' => 'USD', 'gateway_amount_minor' => 193549, 'exchange_rate' => 1550]);

        $this->actingAs($customer)->getJson(route('api.payments.show', $payment->reference))->assertJson(['status' => 'mismatch']);
        $this->assertSame(VehicleStatus::Available, $vehicle->refresh()->status);
    }

    public function test_promo_code_discount_is_applied_and_redeemed_once_paid(): void
    {
        $customer = $this->customer();
        $vehicle = Vehicle::factory()->create(['price' => 10_000_000]);
        PromoCode::factory()->fixed(500_000)->create(['code' => 'SAVE500']);
        $this->actingAs($customer)->postJson(route('api.selection.store'), ['vehicle_id' => $vehicle->id]);

        $this->postJson(route('api.payments.promo'), ['code' => 'save500'])
            ->assertJson(['valid' => true, 'code' => 'SAVE500', 'discount' => 500_000]);

        Http::fake(['api.paystack.co/transaction/initialize' => Http::response(['status' => true, 'message' => 'ok', 'data' => ['access_code' => 'AC']])]);
        $this->postJson(route('api.payments.store'), ['purpose' => 'cart', 'promo' => 'SAVE500'])->assertOk();

        $payment = Payment::query()->sole();
        $this->assertSame('SAVE500', $payment->promo_code);
        $this->assertSame(500_000, $payment->promo_discount);
        $this->assertDatabaseCount('promo_code_redemptions', 0);

        $this->fakeVerify($payment, 'success');
        $this->getJson(route('api.payments.show', $payment->reference))->assertJson(['status' => 'paid']);

        $this->assertDatabaseHas('promo_code_redemptions', ['user_id' => $customer->id, 'order_id' => $payment->id, 'discount_amount' => 500_000]);
        $this->assertSame(1, PromoCode::query()->sole()->times_used);
    }

    public function test_paid_deposit_reserves_the_vehicle_exactly_once(): void
    {
        $customer = $this->customer();
        $vehicle = Vehicle::factory()->create(['price' => 10_000_000]);
        $payment = $this->pendingPayment($customer, $vehicle, 'deposit', 3_000_000);

        $this->fakeVerify($payment, 'success');

        $this->actingAs($customer)->getJson(route('api.payments.show', $payment->reference))->assertJson(['status' => 'paid']);
        $this->getJson(route('api.payments.show', $payment->reference))->assertJson(['status' => 'paid']);

        $this->assertSame(VehicleStatus::Reserved, $vehicle->refresh()->status);
        $this->assertSame(1, $customer->portalNotifications()->count());
        // A settled payment is not re-verified.
        Http::assertSentCount(1);
    }

    public function test_amount_mismatch_is_flagged_for_review(): void
    {
        $customer = $this->customer();
        $vehicle = Vehicle::factory()->create();
        $payment = $this->pendingPayment($customer, $vehicle, 'deposit', 3_000_000);

        $this->fakeVerify($payment, 'success', amountKobo: 100);

        $this->actingAs($customer)->getJson(route('api.payments.show', $payment->reference))->assertJson(['status' => 'mismatch']);
        $this->assertSame(VehicleStatus::Available, $vehicle->refresh()->status);
    }

    public function test_customers_cannot_read_other_customers_payments(): void
    {
        $payment = $this->pendingPayment($this->customer(), Vehicle::factory()->create(), 'deposit', 1000);

        $this->actingAs($this->customer())->getJson(route('api.payments.show', $payment->reference))->assertNotFound();
    }

    public function test_paid_instalment_updates_the_schedule(): void
    {
        $customer = $this->customer();
        $vehicle = Vehicle::factory()->create(['price' => 9_000_000]);
        $schedule = PaymentSchedule::query()->create([
            'schedule_code' => 'JA-PAY-1', 'user_id' => $customer->id, 'vehicle_id' => $vehicle->id,
            'total_price' => 9_000_000, 'deposit' => 3_000_000, 'total_paid' => 3_000_000, 'remaining_balance' => 6_000_000,
            'status' => ScheduleStatus::Active,
        ]);
        foreach ([['Paid', 3_000_000], ['Due', 3_000_000], ['Upcoming', 3_000_000]] as $i => [$status, $amount]) {
            $schedule->instalments()->create(['sequence' => $i + 1, 'due_date' => now()->addMonths($i), 'amount' => $amount, 'status' => $status]);
        }

        Http::fake(['api.paystack.co/transaction/initialize' => Http::response(['status' => true, 'message' => 'ok', 'data' => ['access_code' => 'AC']])]);
        $this->actingAs($customer)
            ->postJson(route('api.payments.store'), ['purpose' => 'instalment', 'vehicle_id' => $vehicle->id])
            ->assertOk();

        $payment = Payment::query()->sole();
        // Next instalment plus the documentation fee.
        $this->assertSame((3_000_000 + 250_000) * 100, $payment->amount_kobo);

        $this->fakeVerify($payment, 'success');
        $this->getJson(route('api.payments.show', $payment->reference))->assertJson(['status' => 'paid']);

        $schedule->refresh();
        $this->assertSame(3_000_000, $schedule->remaining_balance);
        $this->assertSame(6_000_000, $schedule->total_paid);
        $this->assertSame([InstalmentStatus::Paid, InstalmentStatus::Paid, InstalmentStatus::Due], $schedule->instalments->pluck('status')->all());
        $this->assertDatabaseHas('payment_transactions', [
            'schedule_id' => $schedule->id,
            'paystack_reference' => $payment->reference,
            'receipt_url' => '/account/payments/'.$payment->reference.'/receipt',
        ]);

        // The receipt shows the balance left after this instalment, not before it.
        $this->assertSame(3_000_000, $payment->refresh()->outstanding);
        $this->get(route('account.payments.receipt', $payment->reference))
            ->assertOk()
            ->assertSeeInOrder(['Balance remaining', naira(3_000_000)]);
    }

    public function test_paid_payment_has_a_printable_receipt_listed_in_the_portal(): void
    {
        $customer = $this->customer();
        $vehicle = Vehicle::factory()->create(['price' => 10_000_000]);
        $payment = $this->pendingPayment($customer, $vehicle, 'deposit', 3_000_000);

        $this->actingAs($customer)->get(route('account.payments.receipt', $payment->reference))->assertNotFound();

        $this->fakeVerify($payment, 'success');
        $this->getJson(route('api.payments.show', $payment->reference))->assertJson(['status' => 'paid']);

        $this->get(route('account.payments.receipt', $payment->reference))
            ->assertOk()
            ->assertSee('Payment Receipt')
            // The title becomes the "Save as PDF" file name, so it must be file-name safe.
            ->assertSee('<title>Receipt-'.$payment->reference.'-Jayyid-Autos</title>', false)
            ->assertSee($payment->reference)
            ->assertSee($vehicle->title)
            ->assertSee('Reservation deposit')
            ->assertSee('Paystack Card (Card)')
            ->assertSeeInOrder(['Amount paid', naira(3_000_000), 'Balance remaining', naira(7_000_000)]);

        $this->get(route('account.payments'))
            ->assertOk()
            ->assertSee('Payment History &amp; Receipts', false)
            ->assertSee(route('account.payments.receipt', $payment->reference));

        $this->actingAs($this->customer())->get(route('account.payments.receipt', $payment->reference))->assertNotFound();
    }

    public function test_receipt_downloads_as_a_named_pdf(): void
    {
        $customer = $this->customer();
        $vehicle = Vehicle::factory()->create(['price' => 10_000_000]);
        $payment = $this->pendingPayment($customer, $vehicle, 'deposit', 3_000_000);

        $this->actingAs($customer)->get(route('account.payments.receipt.pdf', $payment->reference))->assertNotFound();

        $payment->update(['status' => PaymentStatus::Paid, 'paid_at' => now()]);

        $this->get(route('account.payments.receipt', $payment->reference))
            ->assertSee(route('account.payments.receipt.pdf', $payment->reference))
            ->assertSee('Download PDF');

        $response = $this->get(route('account.payments.receipt.pdf', $payment->reference))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload('Receipt-'.$payment->reference.'-Jayyid-Autos.pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());

        $this->actingAs($this->customer())->get(route('account.payments.receipt.pdf', $payment->reference))->assertNotFound();
    }

    public function test_receipt_email_matches_the_payment_purpose(): void
    {
        config(['mail.default' => 'smtp']);
        Mail::fake();

        $customer = $this->customer();
        $vehicle = Vehicle::factory()->create(['price' => 9_000_000]);
        $schedule = PaymentSchedule::query()->create([
            'schedule_code' => 'JA-PAY-1', 'user_id' => $customer->id, 'vehicle_id' => $vehicle->id,
            'total_price' => 9_000_000, 'deposit' => 3_000_000, 'total_paid' => 3_000_000, 'remaining_balance' => 6_000_000,
            'status' => ScheduleStatus::Active,
        ]);
        $schedule->instalments()->create(['sequence' => 1, 'due_date' => now(), 'amount' => 6_000_000, 'status' => 'Due']);
        $payment = $this->pendingPayment($customer, $vehicle, 'balance', 6_000_000);
        $payment->update(['schedule_id' => $schedule->id, 'outstanding' => 0]);

        $this->fakeVerify($payment, 'success');
        $this->actingAs($customer)->getJson(route('api.payments.show', $payment->reference))->assertJson(['status' => 'paid']);

        Mail::assertSent(PaymentReceipt::class, function (PaymentReceipt $mail) use ($customer, $payment) {
            $html = $mail->render();

            return $mail->hasTo($customer->email)
                && str_contains($html, 'fully settled')
                && ! str_contains($html, 'reserved in your name')
                && str_contains($html, route('account.payments.receipt', $payment->reference));
        });
    }

    public function test_payments_tab_shows_the_plan_and_what_is_due_without_the_checkout(): void
    {
        $customer = $this->customer();
        $vehicle = Vehicle::factory()->create(['price' => 9_000_000]);
        $schedule = PaymentSchedule::query()->create([
            'schedule_code' => 'JA-PAY-7', 'user_id' => $customer->id, 'vehicle_id' => $vehicle->id,
            'total_price' => 9_000_000, 'deposit' => 3_000_000, 'total_paid' => 3_000_000, 'remaining_balance' => 6_000_000,
            'status' => ScheduleStatus::Active,
        ]);
        foreach ([['Paid', 3_000_000], ['Overdue', 3_000_000], ['Upcoming', 3_000_000]] as $i => [$status, $amount]) {
            $schedule->instalments()->create(['sequence' => $i + 1, 'due_date' => now()->addMonths($i - 1), 'amount' => $amount, 'status' => $status]);
        }

        $this->actingAs($customer)->get(route('account.payments'))
            ->assertOk()
            ->assertDontSee('Checkout &amp; Reservation', false)
            ->assertDontSee('checkout-workspace')
            ->assertSee('Your Payment Plan')
            ->assertSee('JA-PAY-7')
            ->assertSee($vehicle->title)
            // Next instalment plus the documentation fee - the amount the server will charge.
            ->assertSee('Pay '.naira(3_250_000).' now')
            ->assertSee('data-pay-purpose="instalment"', false)
            ->assertSee('Pay off full balance ('.naira(6_000_000).')')
            ->assertSee('Overdue')
            ->assertSee('Instalment Schedule');
    }

    public function test_payments_tab_without_a_plan_points_to_checkout(): void
    {
        $customer = $this->customer();
        $vehicle = Vehicle::factory()->create();
        $this->actingAs($customer)->postJson(route('api.selection.store'), ['vehicle_id' => $vehicle->id]);

        $this->get(route('account.payments'))
            ->assertOk()
            ->assertSee('No payment plan yet')
            ->assertSee('1 vehicle</strong> in your selection', false)
            ->assertSee(route('account.reservation'))
            ->assertDontSee('data-pay-panel', false);
    }

    public function test_reservation_checkout_lives_on_its_own_page(): void
    {
        $customer = $this->customer();
        $vehicle = Vehicle::factory()->create(['price' => 10_000_000]);
        $this->actingAs($customer)->postJson(route('api.selection.store'), ['vehicle_id' => $vehicle->id]);

        $this->get(route('account.reservation'))
            ->assertOk()
            ->assertSee('Checkout &amp; Reservation', false)
            ->assertSee('data-done="'.route('account.reservation').'"', false)
            ->assertSee($vehicle->title);

        // Paystack return trip: verified, selection cleared, receipt offered.
        Http::fake(['api.paystack.co/transaction/initialize' => Http::response(['status' => true, 'message' => 'ok', 'data' => ['access_code' => 'AC']])]);
        $this->postJson(route('api.payments.store'), ['purpose' => 'cart'])->assertOk();
        $payment = Payment::query()->sole();
        $this->fakeVerify($payment, 'success');

        $this->get(route('account.reservation', ['reference' => $payment->reference]))
            ->assertOk()
            ->assertSee('Reservation Confirmed')
            ->assertSee(route('account.payments.receipt', $payment->reference));
        $this->getJson(route('api.selection.index'))->assertJsonPath('count', 0);
    }

    public function test_scheduled_payments_require_the_customers_own_schedule(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->actingAs($this->customer())
            ->postJson(route('api.payments.store'), ['purpose' => 'balance', 'vehicle_id' => $vehicle->id])
            ->assertStatus(409);
    }

    public function test_webhook_requires_a_valid_signature(): void
    {
        $payment = $this->pendingPayment($this->customer(), Vehicle::factory()->create(), 'deposit', 1000);
        $body = json_encode(['event' => 'charge.success', 'data' => ['reference' => $payment->reference]]);

        $this->call('POST', route('webhooks.paystack'), [], [], [], ['HTTP_X_PAYSTACK_SIGNATURE' => 'forged', 'CONTENT_TYPE' => 'application/json'], $body)
            ->assertUnauthorized();

        $this->fakeVerify($payment, 'success');
        $signature = hash_hmac('sha512', $body, self::SECRET);

        // The pre-Laravel URL registered in the Paystack dashboard keeps working.
        $this->call('POST', '/api/paystack-webhook.php', [], [], [], ['HTTP_X_PAYSTACK_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'], $body)
            ->assertOk();

        $this->assertSame(PaymentStatus::Paid, $payment->refresh()->status);
    }

    private function pendingPayment(User $customer, Vehicle $vehicle, string $purpose, int $amount): Payment
    {
        return Payment::query()->create([
            'reference' => 'JA-PAY-'.strtoupper(bin2hex(random_bytes(6))),
            'user_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'purpose' => $purpose,
            'method' => 'paystack',
            'amount_kobo' => $amount * 100,
            'vehicle_title' => $vehicle->title,
            'vehicle_price' => $vehicle->price,
            'customer_name' => $customer->full_name,
            'customer_email' => $customer->email,
        ]);
    }

    private function fakeVerify(Payment $payment, string $status, ?int $amountKobo = null): void
    {
        Http::fake([
            'api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'message' => 'Verification successful',
                'data' => [
                    'status' => $status,
                    'amount' => $amountKobo ?? $payment->amount_kobo,
                    'currency' => 'NGN',
                    'gateway_response' => 'Approved',
                    'channel' => 'card',
                    'paid_at' => now()->toIso8601String(),
                ],
            ]),
        ]);
    }
}
