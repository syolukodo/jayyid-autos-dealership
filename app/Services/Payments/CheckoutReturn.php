<?php

namespace App\Services\Payments;

use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use App\Services\Selection\VehicleSelection;
use Illuminate\Http\Request;

/**
 * Gateway return trips (Paystack ?reference=, PayPal ?paypal=1&token=) land on
 * the reservation checkout or the Payments tab. Either way the payment is
 * verified with the gateway before anything is shown as paid.
 */
class CheckoutReturn
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly PayPalGateway $paypal,
        private readonly VehicleSelection $selection,
    ) {}

    /**
     * @return array{notice: string, error: string, paid: ?Payment, preview: bool}
     */
    public function handle(Request $request, User $user): array
    {
        $reference = preg_replace('/[^A-Za-z0-9\-]/', '', (string) $request->query('reference')) ?? '';
        $isPayPal = $request->has('paypal') || $request->has('token');

        // Preview mode: simulate a completed session when no gateway key is set.
        if (! $this->payments->onlinePaymentsAvailable() && $request->query('mock') === '1') {
            if ($request->query('purpose') === 'cart') {
                $this->selection->clear();

                return $this->result('Reservation initiated (preview). Connect your Paystack secret key in .env to process real transactions.', preview: true);
            }

            return $this->result('Payment initiated (preview). Connect your Paystack secret key in .env to process real transactions.');
        }

        if ($isPayPal) {
            if (! $this->paypal->isConfigured()) {
                return $this->result();
            }
            if ($request->query('paypal') === 'cancelled') {
                return $this->result(error: 'PayPal checkout was cancelled. You have not been charged - you can try again.');
            }

            $orderId = (string) ($request->query('token') ?? $request->query('order_id') ?? '');
            $payment = $reference !== ''
                ? Payment::query()->where('reference', $reference)->first()
                : ($orderId !== '' ? Payment::query()->where('gateway_reference', $orderId)->first() : null);

            if ($payment === null) {
                return $this->result(error: 'We could not match that PayPal payment to your account.');
            }

            return $this->outcome($this->payments->settlePayPal($payment, $orderId), $user, 'PayPal');
        }

        if ($reference === '') {
            return $this->result();
        }

        $payment = Payment::query()->where('reference', $reference)->first();
        if ($payment === null) {
            return $this->result(error: 'We could not find that payment on your account.');
        }

        return $this->outcome($this->payments->settlePaystack($payment), $user, 'the bank');
    }

    /**
     * @return array{notice: string, error: string, paid: ?Payment, preview: bool}
     */
    private function outcome(Payment $payment, User $user, string $confirmer): array
    {
        if (! $payment->belongsToUser($user)) {
            return $this->result(error: 'We could not find that payment on your account.');
        }

        return match ($payment->status) {
            PaymentStatus::Failed => $this->result(error: 'The payment was not completed and you have not been charged. You can try again.'),
            PaymentStatus::Mismatch => $this->result(error: 'Your payment needs a manual review. Please contact our sales team with reference '.$payment->reference.'.'),
            PaymentStatus::Pending => $this->result('Reference '.$payment->reference.' is still being confirmed by '.$confirmer.'. Please check back shortly, and do not pay twice.'),
            PaymentStatus::Paid => $this->paid($payment),
        };
    }

    /**
     * @return array{notice: string, error: string, paid: ?Payment, preview: bool}
     */
    private function paid(Payment $payment): array
    {
        if ($payment->purpose === PaymentPurpose::Cart) {
            $this->selection->clear();

            return $this->result('Reservation confirmed. Reference '.$payment->reference.' - your selected vehicles are now reserved while our team prepares your agreement.', paid: $payment);
        }

        return $this->result('Payment received. Reference '.$payment->reference.' has been applied to your schedule.', paid: $payment);
    }

    /**
     * @return array{notice: string, error: string, paid: ?Payment, preview: bool}
     */
    private function result(string $notice = '', string $error = '', ?Payment $paid = null, bool $preview = false): array
    {
        return ['notice' => $notice, 'error' => $error, 'paid' => $paid, 'preview' => $preview];
    }
}
