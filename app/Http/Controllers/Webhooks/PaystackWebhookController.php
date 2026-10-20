<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Payments\PaymentService;
use App\Services\Payments\PaystackGateway;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Settles a payment when Paystack reports charge.success, so a buyer who
 * closes the tab before returning is still marked as paid.
 */
class PaystackWebhookController extends Controller
{
    public function __invoke(Request $request, PaystackGateway $paystack, PaymentService $payments): Response
    {
        if (! $paystack->hasValidSignature($request->getContent(), (string) $request->header('x-paystack-signature'))) {
            return response('', 401);
        }

        if ($request->input('event') === 'charge.success') {
            $payment = Payment::query()->where('reference', (string) $request->input('data.reference'))->first();
            if ($payment !== null) {
                $payments->settlePaystack($payment);
            }
        }

        return response('', 200);
    }
}
