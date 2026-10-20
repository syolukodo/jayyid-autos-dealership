<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Payments\PaymentService;
use App\Services\Payments\PayPalGateway;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * Subscribe to CHECKOUT.ORDER.APPROVED and PAYMENT.CAPTURE.COMPLETED. Each
 * delivery is verified with PayPal, then reconciled like the return trip.
 */
class PayPalWebhookController extends Controller
{
    public function __invoke(Request $request, PayPalGateway $paypal, PaymentService $payments): Response
    {
        $verified = $paypal->hasValidSignature($request->getContent(), [
            'auth_algo' => (string) $request->header('paypal-auth-algo'),
            'cert_url' => (string) $request->header('paypal-cert-url'),
            'transmission_id' => (string) $request->header('paypal-transmission-id'),
            'transmission_sig' => (string) $request->header('paypal-transmission-sig'),
            'transmission_time' => (string) $request->header('paypal-transmission-time'),
        ]);

        if (! $verified) {
            return response('', 401);
        }

        $type = (string) $request->input('event_type');
        if (Str::startsWith($type, ['CHECKOUT.ORDER.', 'PAYMENT.CAPTURE.'])) {
            // An order approval carries the order id directly; a capture carries it in related_ids.
            $orderId = (string) ($request->input('resource.supplementary_data.related_ids.order_id') ?? $request->input('resource.id', ''));
            $reference = (string) $request->input('resource.custom_id', '');

            $payment = match (true) {
                $orderId !== '' => Payment::query()->where('gateway_reference', $orderId)->first(),
                $reference !== '' => Payment::query()->where('reference', $reference)->first(),
                default => null,
            };

            if ($payment !== null) {
                $payments->settlePayPal($payment, $orderId);
            }
        }

        return response('', 200);
    }
}
