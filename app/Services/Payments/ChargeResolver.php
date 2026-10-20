<?php

namespace App\Services\Payments;

use App\Enums\PaymentPurpose;
use App\Enums\VehicleStatus;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Delivery\DeliveryAddress;
use App\Services\Selection\VehicleSelection;

/**
 * Works out what a payment request should charge:
 *   deposit    -> 30% of the vehicle's catalogue price
 *   instalment -> the next scheduled instalment + documentation fee
 *   balance    -> the full remaining balance of the customer's schedule
 *   cart       -> the reservation total for the session selection
 */
class ChargeResolver
{
    public function __construct(private readonly VehicleSelection $selection) {}

    /**
     * @throws PaymentException
     */
    /**
     * @param  ?DeliveryAddress  $delivery  null for showroom pickup (selection checkout only)
     */
    public function resolve(User $customer, PaymentPurpose $purpose, ?string $vehicleId, ?DeliveryAddress $delivery, string $promoCode): Charge
    {
        if (! filter_var($customer->email, FILTER_VALIDATE_EMAIL)) {
            throw new PaymentException('We could not start this payment. Please contact our sales team.');
        }

        return $purpose === PaymentPurpose::Cart
            ? $this->forSelection($customer, $delivery, $promoCode)
            : $this->forVehicle($customer, $purpose, $vehicleId);
    }

    private function forSelection(User $customer, ?DeliveryAddress $delivery, string $promoCode): Charge
    {
        if ($this->selection->count() === 0) {
            throw new PaymentException('Your vehicle selection is empty.');
        }

        // Checked again at payment time: another buyer may have reserved a car since it was selected.
        $taken = $this->selection->unavailable();
        if ($taken->isNotEmpty()) {
            throw new PaymentException(
                $taken->pluck('title')->implode(', ').($taken->count() === 1 ? ' is' : ' are').' no longer available. Remove '.($taken->count() === 1 ? 'it' : 'them').' from your selection to continue.',
                409,
            );
        }
        $count = $this->selection->count();

        if ($delivery !== null && $delivery->address === '') {
            throw new PaymentException('Enter the street address for doorstep delivery.', 422);
        }

        $breakdown = $this->selection->breakdown($delivery, $promoCode, $customer->id);
        if ($delivery !== null && $breakdown->deliveryQuote === null) {
            throw new PaymentException('Choose the state we should deliver to.', 422);
        }
        if ($breakdown->total <= 0) {
            throw new PaymentException('There is nothing to pay in your selection.');
        }

        return new Charge(
            purpose: PaymentPurpose::Cart,
            amount: $breakdown->total,
            vehicleId: null,
            vehicleTitle: $count.' selected vehicle'.($count === 1 ? '' : 's'),
            vehiclePrice: $breakdown->subtotal,
            outstanding: 0,
            promoCode: $breakdown->promoCode,
            promoDiscount: $breakdown->discount,
            delivery: $delivery,
            deliveryFee: $breakdown->logistics,
            vehicleIds: $this->selection->available()->pluck('id')->map(fn ($id) => (int) $id)->all(),
        );
    }

    private function forVehicle(User $customer, PaymentPurpose $purpose, ?string $vehicleId): Charge
    {
        $vehicle = ctype_digit((string) $vehicleId) ? Vehicle::query()->find((int) $vehicleId) : null;
        if ($vehicle === null) {
            throw new PaymentException('We could not find that vehicle.', 404);
        }

        if (! $purpose->isScheduled()) {
            if ($vehicle->status !== VehicleStatus::Available) {
                throw new PaymentException('This vehicle is no longer available for reservation.', 409);
            }

            return new Charge(PaymentPurpose::Deposit, $vehicle->depositAmount(), $vehicle->id, $vehicle->title, $vehicle->price);
        }

        // A scheduled vehicle is already reserved for this buyer, so its status is not checked.
        $schedule = $customer->currentSchedule();
        if ($schedule === null || $schedule->vehicle_id !== $vehicle->id) {
            throw new PaymentException('That vehicle is not part of your signed payment schedule.', 409);
        }

        $amount = $purpose === PaymentPurpose::Instalment
            ? ($schedule->dueAmount() > 0 ? $schedule->dueAmount() + (int) config('showroom.fees.documentation') : 0)
            : $schedule->remaining_balance;

        if ($amount <= 0) {
            throw new PaymentException($purpose === PaymentPurpose::Balance
                ? 'Your plan is already fully settled - there is nothing outstanding to pay.'
                : 'There is no instalment due right now. Please contact our sales team.');
        }

        return new Charge(
            purpose: $purpose,
            amount: $amount,
            vehicleId: $vehicle->id,
            vehicleTitle: $vehicle->title,
            vehiclePrice: $vehicle->price,
            outstanding: $purpose === PaymentPurpose::Instalment ? max(0, $schedule->remaining_balance) : 0,
            scheduleId: $schedule->id,
        );
    }
}
