<?php

namespace App\Services\Delivery;

use App\Enums\VehicleCategory;
use App\Models\Vehicle;
use App\Services\SettingsRepository;
use Illuminate\Support\Collection;

/**
 * Doorstep delivery pricing: each vehicle costs its destination zone's rate
 * scaled by its body type's size factor (config/showroom.php > delivery,
 * overridden from Admin > Settings). The checkout script mirrors this for
 * its live preview via matrix(); the server figure is the one charged.
 */
class DeliveryRates
{
    public function __construct(private readonly SettingsRepository $settings) {}

    /**
     * @return array<string, array{label: string, rate: int, states: list<string>}>
     */
    public function zones(): array
    {
        $zones = [];
        foreach ((array) config('showroom.delivery.zones') as $key => $zone) {
            $zones[$key] = [
                'label' => $zone['label'],
                'rate' => (int) $this->settings->get('delivery_rate_'.$key, (string) $zone['rate']),
                'states' => $zone['states'],
            ];
        }

        return $zones;
    }

    /**
     * Size factor in percent per body type.
     *
     * @return array<string, int>
     */
    public function factors(): array
    {
        $factors = [];
        foreach (VehicleCategory::cases() as $category) {
            $default = (string) (config('showroom.delivery.size_factors.'.$category->value) ?? 100);
            $factors[$category->value] = (int) $this->settings->get('delivery_factor_'.$category->value, $default);
        }

        return $factors;
    }

    /**
     * Every state we deliver to, alphabetically.
     *
     * @return list<string>
     */
    public function states(): array
    {
        $states = collect($this->zones())->pluck('states')->flatten()->sort()->values()->all();

        return $states;
    }

    public function zoneFor(string $state): ?string
    {
        foreach ($this->zones() as $key => $zone) {
            if (in_array($state, $zone['states'], true)) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Price delivery of these vehicles to a state; null when the state is not served.
     *
     * @param  Collection<int, Vehicle>  $vehicles
     */
    public function quote(Collection $vehicles, string $state): ?DeliveryQuote
    {
        $zoneKey = $this->zoneFor($state);
        if ($zoneKey === null) {
            return null;
        }

        $zone = $this->zones()[$zoneKey];
        $factors = $this->factors();

        $lines = $vehicles->map(fn (Vehicle $vehicle) => [
            'title' => $vehicle->title,
            'amount' => $this->perVehicle($zone['rate'], $factors[$vehicle->category->value] ?? 100),
        ])->values()->all();

        return new DeliveryQuote($state, $zone['label'], array_sum(array_column($lines, 'amount')), $lines);
    }

    /**
     * What the checkout script needs to preview the fee without a round trip.
     *
     * @return array{states: array<string, string>, rates: array<string, int>, factors: array<string, int>}
     */
    public function matrix(): array
    {
        $zones = $this->zones();
        $states = [];
        foreach ($zones as $key => $zone) {
            foreach ($zone['states'] as $state) {
                $states[$state] = $key;
            }
        }

        return [
            'states' => $states,
            'rates' => array_map(fn (array $zone) => $zone['rate'], $zones),
            'factors' => $this->factors(),
        ];
    }

    public function lowestRate(): int
    {
        return (int) collect($this->zones())->min('rate');
    }

    private function perVehicle(int $rate, int $factor): int
    {
        return (int) (round($rate * $factor / 100 / 1000) * 1000);
    }
}
