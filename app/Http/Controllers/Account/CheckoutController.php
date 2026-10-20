<?php

namespace App\Http\Controllers\Account;

use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Vehicle;
use App\Services\Payments\PaymentService;
use App\Services\Payments\PaystackGateway;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Single-vehicle deposit checkout (30% down, balance on a signed schedule).
 * The page finishes the Paystack payment in place and reloads with
 * ?reference=, which is verified before any result is shown.
 */
class CheckoutController extends Controller
{
    public function __invoke(Request $request, Vehicle $vehicle, PaystackGateway $paystack, PaymentService $payments): View
    {
        $live = $paystack->isConfigured();
        $error = '';
        $payment = null;

        $reference = preg_replace('/[^A-Za-z0-9\-]/', '', (string) $request->query('reference')) ?? '';
        if ($live && $reference !== '') {
            $payment = Payment::query()->where('reference', $reference)->first();
            $payment = $payment ? $payments->settlePaystack($payment) : null;

            if ($payment === null || ! $payment->belongsToUser($request->user()) || $payment->vehicle_id !== $vehicle->id
                || $payment->purpose !== PaymentPurpose::Deposit) {
                $payment = null;
                $error = 'We could not find that payment on your account.';
            } elseif ($payment->status === PaymentStatus::Failed) {
                $error = 'The payment was not completed and you have not been charged. You can try again below.';
            } elseif ($payment->status === PaymentStatus::Mismatch) {
                $error = 'Your payment needs a manual review. Please contact our sales team with reference '.$payment->reference.'.';
            }
        }

        $method = in_array($request->query('method'), ['paystack', 'transfer'], true) ? $request->query('method') : 'paystack';
        $deposit = $vehicle->depositAmount();
        $balance = $vehicle->price - $deposit;
        $instalments = (int) config('showroom.max_instalments');

        return view('account.checkout', [
            'vehicle' => $vehicle,
            'live' => $live,
            'payError' => $error,
            'payment' => $payment,
            'paid' => $payment?->status === PaymentStatus::Paid,
            'processing' => $payment?->status === PaymentStatus::Pending,
            'mockPaid' => ! $live && $request->query('mock') === '1',
            'method' => $method,
            'deposit' => $deposit,
            'balance' => $balance,
            'perMonth' => (int) ceil($balance / $instalments),
            'instalments' => $instalments,
            'depositPercent' => $vehicle->price > 0 ? (int) round($deposit / $vehicle->price * 100) : 0,
        ]);
    }
}
