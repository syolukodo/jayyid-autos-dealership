<?php

namespace App\Http\Controllers\Api;

use App\Enums\PaymentMethod;
use App\Enums\PaymentPurpose;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Delivery\DeliveryAddress;
use App\Services\Payments\ChargeResolver;
use App\Services\Payments\PaymentException;
use App\Services\Payments\PaymentService;
use App\Services\Promo\PromoCodeEvaluator;
use App\Services\Selection\VehicleSelection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Behind the on-site checkout scripts: opens payments, validates promo
 * codes and reports a payment's status after reconciling with the gateway.
 */
class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $payments) {}

    public function store(Request $request, ChargeResolver $charges): JsonResponse
    {
        if (! $this->payments->onlinePaymentsAvailable()) {
            return response()->json(['ok' => false, 'message' => 'Online payment is not available yet.'], 503);
        }

        $purpose = PaymentPurpose::tryFrom((string) $request->input('purpose')) ?? PaymentPurpose::Deposit;
        $method = PaymentMethod::tryFrom((string) $request->input('method')) ?? PaymentMethod::Paystack;

        try {
            $charge = $charges->resolve(
                $request->user(),
                $purpose,
                $request->input('vehicle_id'),
                $request->input('delivery') === 'delivery'
                    ? new DeliveryAddress(
                        Str::limit(trim((string) $request->input('delivery_state')), 50, ''),
                        Str::limit(trim((string) $request->input('delivery_city')), 100, ''),
                        Str::limit(trim((string) $request->input('delivery_address')), 255, ''),
                    )
                    : null,
                (string) $request->input('promo', ''),
            );

            // Release the session lock so status polls are not queued behind the gateway call.
            $request->session()->save();

            return response()->json($this->payments->start($request->user(), $charge, $method));
        } catch (PaymentException $exception) {
            return response()->json(['ok' => false, 'message' => $exception->getMessage()], $exception->status);
        }
    }

    /**
     * Live validation for the checkout "Apply" button; re-checked when the payment starts.
     */
    public function promo(Request $request, PromoCodeEvaluator $promos, VehicleSelection $selection): JsonResponse
    {
        $result = $promos->evaluate((string) $request->input('code', ''), $selection->promoContext($request->user()->id));

        return response()->json([
            'ok' => true,
            'valid' => $result->valid,
            'code' => $result->valid ? $result->code : '',
            'discount' => $result->discount,
            'discount_label' => $result->valid ? naira($result->discount) : '',
            'message' => $result->message,
        ]);
    }

    public function show(Request $request, string $reference): JsonResponse
    {
        $payment = Payment::query()->where('reference', $reference)->first();

        if ($payment === null || ! $payment->belongsToUser($request->user())) {
            return response()->json(['ok' => false, 'message' => 'We could not find that payment.'], 404);
        }

        return response()->json(['ok' => true, 'status' => $this->payments->settle($payment)->status->value])
            ->header('Cache-Control', 'no-store');
    }
}
