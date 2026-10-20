<?php

namespace App\Services\Payments;

use App\Enums\InstalmentStatus;
use App\Enums\NotificationType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Enums\ScheduleStatus;
use App\Enums\VehicleStatus;
use App\Mail\PaymentReceipt;
use App\Models\Payment;
use App\Models\PaymentSchedule;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Notifier;
use App\Services\Promo\PromoCodeEvaluator;
use App\Support\TransactionalMail;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * The payments table: records every attempt and applies a confirmed payment
 * to the showroom records exactly once, whichever gateway settled it.
 */
class PaymentLedger
{
    public function __construct(
        private readonly Notifier $notifier,
        private readonly PromoCodeEvaluator $promos,
    ) {}

    public function record(User $customer, Charge $charge, PaymentMethod $method): Payment
    {
        return Payment::query()->create([
            'reference' => 'JA-PAY-'.mb_strtoupper(bin2hex(random_bytes(6))),
            'user_id' => $customer->id,
            'vehicle_id' => $charge->vehicleId,
            'schedule_id' => $charge->scheduleId,
            'purpose' => $charge->purpose,
            'method' => $method,
            'amount_kobo' => $charge->amount * 100,
            'outstanding' => $charge->outstanding,
            'vehicle_title' => $charge->vehicleTitle,
            'vehicle_price' => $charge->vehiclePrice,
            'customer_name' => $customer->full_name,
            'customer_email' => $customer->email,
            'promo_code' => $charge->promoCode !== '' ? $charge->promoCode : null,
            'promo_discount' => $charge->promoDiscount > 0 ? $charge->promoDiscount : null,
            'delivery_fee' => $charge->delivery !== null ? $charge->deliveryFee : null,
            'delivery_state' => $charge->delivery?->state,
            'delivery_city' => $charge->delivery?->city ?: null,
            'delivery_address' => $charge->delivery?->address,
            'vehicle_ids' => $charge->vehicleIds !== [] ? $charge->vehicleIds : null,
        ]);
    }

    public function markFailed(Payment $payment, string $message): void
    {
        $payment->update(['status' => PaymentStatus::Failed, 'gateway_message' => Str::limit($message, 255, '')]);
    }

    /**
     * Move a payment to the state the gateway reported. Only the request that
     * flips the row to "paid" applies it (conditional UPDATE inside a
     * transaction), so a repeat call or a racing webhook cannot double-apply.
     */
    public function finalize(
        Payment $payment,
        PaymentStatus $status,
        string $gatewayStatus,
        string $gatewayMessage,
        string $channel,
        ?CarbonInterface $paidAt,
    ): Payment {
        if ($payment->isPaid() || $payment->status === PaymentStatus::Mismatch) {
            return $payment;
        }

        /** @var list<string> $taken vehicles another buyer reserved first */
        $taken = [];

        $newlyPaid = DB::transaction(function () use ($payment, $status, $gatewayStatus, $gatewayMessage, $channel, $paidAt, &$taken): bool {
            $changed = Payment::query()
                ->whereKey($payment->id)
                ->whereNotIn('status', [PaymentStatus::Paid, PaymentStatus::Mismatch])
                ->update(array_filter([
                    'status' => $status,
                    'gateway_status' => Str::limit($gatewayStatus, 50, ''),
                    'gateway_message' => Str::limit($gatewayMessage, 255, ''),
                    'channel' => Str::limit($channel, 50, ''),
                    'paid_at' => $paidAt,
                    'updated_at' => now(),
                ], fn ($value) => $value !== null));

            if ($changed !== 1 || $status !== PaymentStatus::Paid) {
                return false;
            }

            $payment->refresh();
            $taken = $this->apply($payment);

            if ($taken !== []) {
                // Charged, but another buyer reserved a vehicle first: hold the rest and hand it to staff.
                $payment->update([
                    'status' => PaymentStatus::Mismatch,
                    'gateway_message' => Str::limit('Paid, but no longer available: '.implode(', ', $taken), 255, ''),
                ]);

                return false;
            }

            $this->promos->redeem($payment);

            return true;
        });

        $payment->refresh();

        if ($taken !== []) {
            $this->reportTaken($payment, $taken);
        }

        if ($newlyPaid) {
            TransactionalMail::send($payment->customer_email, new PaymentReceipt($payment));
            if ($payment->user_id) {
                $this->notifier->notify(
                    $payment->user_id,
                    NotificationType::Payment,
                    'Payment received',
                    'We received '.naira($payment->amount()).' for the '.$payment->vehicle_title.' ('.$payment->reference.').',
                    'account/payments',
                );
            }
        }

        return $payment;
    }

    /**
     * A deposit or selection payment reserves its vehicles; an instalment or
     * settlement updates the schedule.
     *
     * @return list<string> titles of vehicles another buyer reserved first
     */
    private function apply(Payment $payment): array
    {
        if ($payment->purpose === PaymentPurpose::Deposit) {
            return $payment->vehicle_id ? $this->reserve([$payment->vehicle_id]) : [];
        }

        if ($payment->purpose === PaymentPurpose::Cart) {
            return $this->reserve($payment->vehicle_ids ?? []);
        }

        $schedule = $payment->schedule_id
            ? PaymentSchedule::query()->lockForUpdate()->find($payment->schedule_id)
            : null;
        if ($schedule === null) {
            Log::warning('Paid payment has no schedule to apply to', ['reference' => $payment->reference]);

            return [];
        }

        $applied = $payment->purpose === PaymentPurpose::Balance
            ? $this->settleSchedule($schedule)
            : $this->payNextInstalment($schedule, $payment->amount());

        $remaining = max(0, $schedule->remaining_balance - $applied);
        $open = $schedule->instalments()->where('status', '<>', InstalmentStatus::Paid)->count();

        $schedule->update([
            'total_paid' => $schedule->total_paid + $applied,
            'remaining_balance' => $remaining,
            'status' => ($remaining <= 0 || $open === 0) ? ScheduleStatus::Completed : ScheduleStatus::Active,
        ]);

        $this->recordScheduleTransaction($payment, $schedule);

        // The charge recorded the balance before this payment; the receipt must show what is left after it.
        $payment->update(['outstanding' => $remaining]);

        return [];
    }

    /**
     * Reserve each vehicle that is still Available. The conditional UPDATE means
     * two buyers paying for the same car at once cannot both get it.
     *
     * @param  list<int>  $vehicleIds
     * @return list<string> titles of vehicles that were no longer available
     */
    private function reserve(array $vehicleIds): array
    {
        $taken = [];
        foreach (array_unique($vehicleIds) as $id) {
            $reserved = Vehicle::query()
                ->whereKey($id)
                ->where('status', VehicleStatus::Available)
                ->update(['status' => VehicleStatus::Reserved, 'updated_at' => now()]);

            if ($reserved === 0) {
                $taken[] = Vehicle::query()->whereKey($id)->value('title') ?? 'Vehicle #'.$id;
            }
        }

        return $taken;
    }

    /**
     * Tell staff to refund or offer an alternative, and tell the buyer we are on it.
     *
     * @param  list<string>  $taken
     */
    private function reportTaken(Payment $payment, array $taken): void
    {
        $list = implode(', ', $taken);

        $this->notifier->notifyStaff(
            NotificationType::Payment,
            'Payment needs review: vehicle already reserved',
            $payment->customer_name.' paid '.naira($payment->amount()).' ('.$payment->reference.') but '.$list.' had already been reserved by another buyer. Arrange a refund or an alternative.',
            'admin/payments?q='.$payment->reference.'#customer-payments',
        );

        if ($payment->user_id) {
            $this->notifier->notify(
                $payment->user_id,
                NotificationType::Payment,
                'Payment received - our team will contact you',
                'We received '.naira($payment->amount()).' ('.$payment->reference.'), but '.$list.' was reserved by another buyer moments earlier. Our sales team will contact you about a full refund or an alternative.',
                'account/payments',
            );
        }
    }

    private function recordScheduleTransaction(Payment $payment, PaymentSchedule $schedule): void
    {
        $schedule->transactions()->create([
            'paystack_reference' => $payment->reference,
            'amount_paid' => $payment->amount(),
            'payment_method' => $payment->method->label(),
            'payment_date' => now(),
            'receipt_url' => route('account.payments.receipt', $payment->reference, false),
        ]);
    }

    private function settleSchedule(PaymentSchedule $schedule): int
    {
        $schedule->instalments()
            ->where('status', '<>', InstalmentStatus::Paid)
            ->update(['status' => InstalmentStatus::Paid, 'paid_at' => now(), 'updated_at' => now()]);

        return $schedule->remaining_balance;
    }

    private function payNextInstalment(PaymentSchedule $schedule, int $amountPaid): int
    {
        $next = $schedule->instalments()->where('status', '<>', InstalmentStatus::Paid)->first();
        if ($next === null) {
            return max(0, $amountPaid - (int) config('showroom.fees.documentation'));
        }

        $next->update(['status' => InstalmentStatus::Paid, 'paid_at' => now()]);

        // The following instalment becomes the one due.
        $schedule->instalments()
            ->where('status', InstalmentStatus::Upcoming)
            ->first()
            ?->update(['status' => InstalmentStatus::Due]);

        return $next->amount;
    }
}
