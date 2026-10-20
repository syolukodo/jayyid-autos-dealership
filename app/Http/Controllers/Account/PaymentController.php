<?php

namespace App\Http\Controllers\Account;

use App\Enums\InstalmentStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentInstalment;
use App\Services\Payments\CheckoutReturn;
use App\Services\Payments\PayPalConversion;
use App\Services\Payments\PayPalGateway;
use App\Services\Payments\PaystackGateway;
use App\Services\Selection\VehicleSelection;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "Payments & Instalments": what the buyer owes, what is due next (with an
 * online pay button), their instalment schedule and every receipt. Gateway
 * return trips for instalment/balance payments land here and are verified
 * before anything is shown as paid. Reserving new vehicles lives on the
 * checkout page (account.reservation).
 */
class PaymentController extends Controller
{
    public function __invoke(
        Request $request,
        CheckoutReturn $return,
        PaystackGateway $paystack,
        PayPalGateway $paypal,
        VehicleSelection $selection,
        PayPalConversion $paypalConversion,
    ): View {
        $user = $request->user();
        $result = $return->handle($request, $user);

        $schedule = $user->currentSchedule();
        $instalments = $schedule?->instalments ?? collect();
        $nextInstalment = $instalments->first(fn (PaymentInstalment $i) => $i->displayStatus() === InstalmentStatus::Due)
            ?? $instalments->first(fn (PaymentInstalment $i) => $i->status !== InstalmentStatus::Paid);

        $payments = Payment::query()->madeBy($user)->latest()->orderByDesc('id')->get();
        $receipts = $payments->where('status', PaymentStatus::Paid)->sortByDesc(fn (Payment $p) => $p->paidOn())->values();

        return view('account.payments', [
            'notice' => $result['notice'],
            'payError' => $result['error'],
            'justPaid' => $result['paid'],
            'schedule' => $schedule,
            'instalments' => $instalments,
            'paidCount' => $instalments->where('status', InstalmentStatus::Paid)->count(),
            'nextInstalment' => $nextInstalment,
            'documentationFee' => (int) config('showroom.fees.documentation'),
            'receipts' => $receipts,
            'totalPaid' => $receipts->sum(fn (Payment $p) => $p->amount()),
            // Bank transfers still waiting to land, and payments flagged for staff review. Closed card
            // windows also leave pending rows, so only transfers are worth surfacing as "in progress".
            'awaiting' => $payments->filter(fn (Payment $p) => $p->status === PaymentStatus::Mismatch
                || ($p->status === PaymentStatus::Pending && $p->method === PaymentMethod::Transfer && $p->created_at?->gt(now()->subDay()))),
            'selectionCount' => $selection->count(),
            'paystackLive' => $paystack->isConfigured(),
            // PayPal needs an exchange rate as well as keys (it does not settle in naira).
            'paypalLive' => $paypal->isConfigured() && $paypalConversion->available(),
            'paypalConversion' => $paypalConversion,
        ]);
    }
}
