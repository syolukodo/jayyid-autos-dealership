<?php

namespace App\Services\Payments;

use App\Enums\PaymentMethod;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Opens payments with Paystack or PayPal and reconciles them against the
 * gateway. The browser is never trusted for the outcome: a payment is only
 * marked paid when the gateway's status, amount and currency all match.
 */
class PaymentService
{
    public function __construct(
        private readonly PaymentLedger $ledger,
        private readonly PaystackGateway $paystack,
        private readonly PayPalGateway $paypal,
        private readonly PayPalConversion $conversion,
    ) {}

    public function onlinePaymentsAvailable(): bool
    {
        return $this->paystack->isConfigured() || $this->paypal->isConfigured();
    }

    /**
     * Record a pending payment and open it with the chosen gateway.
     *
     * @return array<string, mixed> JSON payload for the checkout script
     *
     * @throws PaymentException
     */
    public function start(User $customer, Charge $charge, PaymentMethod $method): array
    {
        return $method === PaymentMethod::PayPal
            ? $this->startPayPal($customer, $charge)
            : $this->startPaystack($customer, $charge, $method);
    }

    /**
     * Reconcile a Paystack payment (status poll, return trip or webhook).
     */
    public function settlePaystack(Payment $payment): Payment
    {
        if ($payment->isPaid() || ! $this->paystack->isConfigured()) {
            return $payment;
        }

        $response = $this->paystack->verify($payment->reference);
        if (! $response->ok) {
            return $payment;
        }

        $tx = $response->data;
        $gatewayStatus = (string) ($tx['status'] ?? '');

        $status = match (true) {
            $gatewayStatus === 'success' => (int) ($tx['amount'] ?? 0) === $payment->amount_kobo
                && strtoupper((string) ($tx['currency'] ?? '')) === $this->paystack->currency()
                    ? PaymentStatus::Paid
                    : PaymentStatus::Mismatch,
            in_array($gatewayStatus, ['failed', 'abandoned', 'reversed'], true) => PaymentStatus::Failed,
            default => PaymentStatus::Pending,
        };

        return $this->ledger->finalize(
            $payment,
            $status,
            $gatewayStatus,
            (string) ($tx['gateway_response'] ?? ''),
            (string) ($tx['channel'] ?? ''),
            $status === PaymentStatus::Paid ? Carbon::parse($tx['paid_at'] ?? 'now') : null,
        );
    }

    /**
     * Capture and reconcile a PayPal order (return trip or webhook).
     */
    public function settlePayPal(Payment $payment): Payment
    {
        if ($payment->isPaid() || ! $this->paypal->isConfigured()) {
            return $payment;
        }

        // Only ever the order this payment opened. An id from the browser (?token=) could name another,
        // already-captured order for the same amount, and one PayPal charge would settle two payments.
        $orderId = (string) $payment->gateway_reference;
        if ($orderId === '') {
            return $payment;
        }

        $result = $this->paypal->captureOrder($orderId);
        if (! $result->ok || ! isset($result->data['status'])) {
            // Not approved yet (ORDER_NOT_APPROVED) or PayPal unreachable: settle later.
            return $payment;
        }

        $order = $result->data;
        $gatewayStatus = (string) $order['status'];
        $capture = (array) ($order['purchase_units'][0]['payments']['captures'][0] ?? []);
        $captureStatus = (string) ($capture['status'] ?? '');
        $amount = (array) ($capture['amount'] ?? ($order['purchase_units'][0]['amount'] ?? []));

        $status = match (true) {
            // Compare with exactly what this order was opened for (older rows were opened in naira).
            $gatewayStatus === 'COMPLETED' && $captureStatus === 'COMPLETED' => (int) round((float) ($amount['value'] ?? 0) * 100) === ($payment->gateway_amount_minor ?? $payment->amount_kobo)
                && strtoupper((string) ($amount['currency_code'] ?? '')) === ($payment->gateway_currency ?? $this->paypal->currency())
                    ? PaymentStatus::Paid
                    : PaymentStatus::Mismatch,
            in_array($captureStatus, ['DENIED', 'FAILED'], true),
            in_array($gatewayStatus, ['PAYMENT_SOURCE_REJECTED', 'VOIDED'], true) => PaymentStatus::Failed,
            default => PaymentStatus::Pending,
        };

        $message = (string) ($capture['processor_response']['response_message'] ?? $captureStatus);

        return $this->ledger->finalize(
            $payment,
            $status,
            $gatewayStatus,
            $message !== '' ? $message : $gatewayStatus,
            'paypal',
            $status === PaymentStatus::Paid ? Carbon::parse($capture['create_time'] ?? $capture['update_time'] ?? 'now') : null,
        );
    }

    /**
     * Settle whichever method the payment was opened with.
     */
    public function settle(Payment $payment): Payment
    {
        return $payment->method === PaymentMethod::PayPal
            ? $this->settlePayPal($payment)
            : $this->settlePaystack($payment);
    }

    /**
     * @return array<string, mixed>
     */
    private function startPaystack(User $customer, Charge $charge, PaymentMethod $method): array
    {
        if (! $this->paystack->isConfigured()) {
            throw new PaymentException('Card payments are not available yet. Please try PayPal or contact our sales team.', 503);
        }

        $payment = $this->ledger->record($customer, $charge, $method);
        $payload = [
            'email' => $customer->email,
            'amount' => $payment->amount_kobo,
            'currency' => $this->paystack->currency(),
            'reference' => $payment->reference,
            'metadata' => [
                'purpose' => $charge->purpose->value,
                'vehicle_id' => (string) ($charge->vehicleId ?? 'ja-selection'),
                'custom_fields' => [
                    ['display_name' => 'Vehicle', 'variable_name' => 'vehicle', 'value' => $charge->vehicleTitle],
                    ['display_name' => 'Customer', 'variable_name' => 'customer', 'value' => $customer->full_name],
                ],
            ],
        ];

        if ($method === PaymentMethod::Transfer) {
            $expires = now()->addSeconds((int) config('services.paystack.transfer_ttl'))->utc()->format('Y-m-d\TH:i:s\Z');
            $response = $this->paystack->chargeBankTransfer($payload + ['bank_transfer' => ['account_expires_at' => $expires]]);
            $transfer = [
                'account_number' => (string) ($response->data['account_number'] ?? ''),
                'account_name' => (string) ($response->data['account_name'] ?? ''),
                'bank' => (string) ($response->data['bank']['name'] ?? ''),
                'expires_at' => (string) ($response->data['account_expires_at'] ?? $expires),
            ];

            if (! $response->ok || $transfer['account_number'] === '') {
                $this->fail($payment, $response->message);
                throw new PaymentException('We could not generate a transfer account right now. Please try again or pay by card.', 502);
            }

            return ['ok' => true, 'method' => $method->value, 'reference' => $payment->reference, 'access_code' => '', 'transfer' => $transfer];
        }

        // Bank transfer has its own on-page flow, so it is left out of the card window.
        $response = $this->paystack->initializeTransaction($payload + ['channels' => ['card', 'bank', 'ussd', 'apple_pay']]);
        $accessCode = (string) ($response->data['access_code'] ?? '');

        if (! $response->ok || $accessCode === '') {
            $this->fail($payment, $response->message);
            throw new PaymentException('We could not reach the payment gateway. Please try again in a moment.', 502);
        }

        return ['ok' => true, 'method' => $method->value, 'reference' => $payment->reference, 'access_code' => $accessCode, 'transfer' => []];
    }

    /**
     * @return array<string, mixed>
     */
    private function startPayPal(User $customer, Charge $charge): array
    {
        if (! $this->paypal->isConfigured()) {
            throw new PaymentException('PayPal checkout is not available yet. Please pay by card instead.', 503);
        }

        $converted = $this->conversion->convert($charge->amount);
        if ($converted === null) {
            throw new PaymentException('PayPal is not available right now. Please pay by card or bank transfer.', 503);
        }

        $payment = $this->ledger->record($customer, $charge, PaymentMethod::PayPal);
        $payment->update([
            'gateway_currency' => $converted->currency,
            'gateway_amount_minor' => $converted->minor,
            'exchange_rate' => $converted->rate,
        ]);
        $returnRoute = $charge->purpose === PaymentPurpose::Cart ? 'account.reservation' : 'account.payments';
        $returnUrl = route($returnRoute, ['paypal' => 1, 'reference' => $payment->reference]);

        $response = $this->paypal->createOrder([
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => $payment->reference,
                'custom_id' => $payment->reference,
                'description' => $charge->vehicleTitle,
                'amount' => [
                    'currency_code' => $converted->currency,
                    'value' => $converted->value(),
                ],
            ]],
            'application_context' => [
                'brand_name' => config('app.name'),
                'shipping_preference' => 'NO_SHIPPING',
                'user_action' => 'PAY_NOW',
                'return_url' => $returnUrl,
                'cancel_url' => route($returnRoute, ['paypal' => 'cancelled', 'reference' => $payment->reference]),
            ],
        ]);

        $orderId = (string) ($response->data['id'] ?? '');
        $approveUrl = (string) (collect($response->data['links'] ?? [])->firstWhere('rel', 'approve')['href'] ?? '');

        if (! $response->ok || $orderId === '' || $approveUrl === '') {
            $message = $response->message !== '' ? $response->message : 'We could not open the PayPal checkout.';
            $this->fail($payment, $message);
            throw new PaymentException($message, 502);
        }

        $payment->update(['gateway_reference' => $orderId]);

        return [
            'ok' => true, 'method' => 'paypal', 'reference' => $payment->reference, 'order_id' => $orderId, 'approve_url' => $approveUrl,
            'charged' => $converted->label(),
        ];
    }

    private function fail(Payment $payment, string $message): void
    {
        $this->ledger->markFailed($payment, $message);
        Log::warning('Payment could not be opened', ['reference' => $payment->reference, 'method' => $payment->method->value, 'message' => $message]);
    }
}
