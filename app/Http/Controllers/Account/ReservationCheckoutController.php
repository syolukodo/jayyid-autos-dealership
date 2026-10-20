<?php

namespace App\Http\Controllers\Account;

use App\Enums\PaymentPurpose;
use App\Http\Controllers\Controller;
use App\Services\Delivery\DeliveryRates;
use App\Services\Payments\CheckoutReturn;
use App\Services\Payments\PayPalConversion;
use App\Services\Payments\PayPalGateway;
use App\Services\Payments\PaystackGateway;
use App\Services\Selection\VehicleSelection;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The 4-step reservation checkout for the vehicle selection. Gateway return
 * trips for a selection payment land back here and are verified first.
 */
class ReservationCheckoutController extends Controller
{
    public function __invoke(
        Request $request,
        CheckoutReturn $return,
        PaystackGateway $paystack,
        PayPalGateway $paypal,
        VehicleSelection $selection,
        DeliveryRates $deliveryRates,
        PayPalConversion $paypalConversion,
    ): View {
        $result = $return->handle($request, $request->user());

        $paidReference = match (true) {
            $result['preview'] => 'preview',
            $result['paid']?->purpose === PaymentPurpose::Cart => $result['paid']->reference,
            default => '',
        };

        return view('account.reservation-checkout', [
            'notice' => $result['notice'],
            'payError' => $result['error'],
            'paidReference' => $paidReference,
            'selectionVehicles' => $selection->vehicles(),
            'unavailableCount' => $selection->unavailable()->count(),
            'cart' => $selection->breakdown(),
            'live' => $paystack->isConfigured() || $paypal->isConfigured(),
            'paystackLive' => $paystack->isConfigured(),
            'customer' => $request->user(),
            'deliveryStates' => $deliveryRates->states(),
            'deliveryMatrix' => $deliveryRates->matrix(),
            'deliveryFrom' => $deliveryRates->lowestRate(),
            'paypalCurrency' => $paypalConversion->currency(),
            'paypalRate' => $paypal->isConfigured() ? $paypalConversion->rate() : null,
        ]);
    }
}
