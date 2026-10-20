<?php

namespace App\Services\Selection;

use App\Models\Vehicle;
use App\Services\Delivery\DeliveryAddress;
use App\Services\Delivery\DeliveryRates;
use App\Services\Promo\PromoCodeEvaluator;
use App\Services\Promo\PromoContext;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;

/**
 * The visitor's vehicle selection (mini-cart). The session is the source of
 * truth for checkout; public pages mirror it in localStorage for instant UI.
 */
class VehicleSelection
{
    private const SESSION_KEY = 'vehicle_selection';

    /** @var Collection<int, Vehicle>|null */
    private ?Collection $vehicles = null;

    public function __construct(
        private readonly Session $session,
        private readonly PromoCodeEvaluator $promos,
        private readonly DeliveryRates $deliveryRates,
    ) {}

    /**
     * Selected vehicles in the order they were added; ids no longer in the catalogue are dropped.
     *
     * @return Collection<int, Vehicle>
     */
    public function vehicles(): Collection
    {
        if ($this->vehicles !== null) {
            return $this->vehicles;
        }

        $ids = $this->storedIds();
        $found = Vehicle::query()->whereKey($ids)->get()->keyBy('id');
        $vehicles = collect($ids)
            ->filter(fn (int $id) => $found->has($id))
            ->map(fn (int $id) => $found->get($id))
            ->values();

        if ($vehicles->count() !== count($ids)) {
            $this->store($vehicles->pluck('id')->all());
        }

        return $this->vehicles = $vehicles;
    }

    public function add(Vehicle $vehicle): void
    {
        $ids = $this->storedIds();
        if (! in_array($vehicle->id, $ids, true)) {
            $ids[] = $vehicle->id;
            $this->store($ids);
        }
    }

    public function remove(int $vehicleId): void
    {
        $this->store(array_values(array_filter($this->storedIds(), fn (int $id) => $id !== $vehicleId)));
    }

    public function clear(): void
    {
        $this->store([]);
    }

    public function count(): int
    {
        return $this->vehicles()->count();
    }

    /**
     * Selected vehicles that can still be bought; only these are priced and paid for.
     *
     * @return Collection<int, Vehicle>
     */
    public function available(): Collection
    {
        return $this->vehicles()->filter(fn (Vehicle $vehicle) => $vehicle->isAvailable())->values();
    }

    /**
     * Selected vehicles that were reserved or sold after they were added.
     *
     * @return Collection<int, Vehicle>
     */
    public function unavailable(): Collection
    {
        return $this->vehicles()->reject(fn (Vehicle $vehicle) => $vehicle->isAvailable())->values();
    }

    public function subtotal(): int
    {
        return (int) $this->available()->sum('price');
    }

    public function promoContext(?int $userId): PromoContext
    {
        return new PromoContext($this->available(), $userId);
    }

    /**
     * Totals for the selection; $delivery is null for showroom pickup. A
     * delivery state we do not serve yields no quote and no logistics fee.
     */
    public function breakdown(?DeliveryAddress $delivery = null, string $promoCode = '', ?int $userId = null): CheckoutBreakdown
    {
        $subtotal = $this->subtotal();
        $hasItems = $subtotal > 0;
        $taxRate = (float) config('showroom.tax_rate');

        $documentation = $hasItems ? (int) config('showroom.fees.documentation') : 0;
        $tax = (int) round($subtotal * $taxRate);
        $quote = $delivery !== null && $hasItems ? $this->deliveryRates->quote($this->available(), $delivery->state) : null;
        $logistics = $quote?->fee ?? 0;

        $promo = $promoCode !== '' ? $this->promos->evaluate($promoCode, $this->promoContext($userId)) : null;
        $discount = $promo?->valid ? $promo->discount : 0;

        return new CheckoutBreakdown(
            count: $this->available()->count(),
            subtotal: $subtotal,
            documentation: $documentation,
            tax: $tax,
            taxRate: $taxRate,
            logistics: $logistics,
            discount: $discount,
            total: max(0, $subtotal + $documentation + $tax + $logistics - $discount),
            delivery: $delivery !== null,
            deliveryQuote: $quote,
            promoCode: $promo?->valid ? $promo->code : '',
            promoMessage: $promo?->valid ? $promo->message : '',
        );
    }

    /**
     * Payload for the selection JSON endpoint and the drawer's localStorage mirror.
     *
     * @return array{ok: true, count: int, subtotal: int, subtotal_label: string, items: list<array<string, mixed>>}
     */
    public function toArray(): array
    {
        $subtotal = $this->subtotal();

        return [
            'ok' => true,
            'count' => $this->count(),
            'subtotal' => $subtotal,
            'subtotal_label' => naira($subtotal),
            'items' => $this->vehicles()->map(fn (Vehicle $vehicle) => $vehicle->toSelectionArray())->all(),
        ];
    }

    /**
     * @return list<int>
     */
    private function storedIds(): array
    {
        $raw = $this->session->get(self::SESSION_KEY, []);

        return array_values(array_unique(array_filter(
            array_map('intval', is_array($raw) ? $raw : []),
            fn (int $id) => $id > 0
        )));
    }

    /**
     * @param  list<int>  $ids
     */
    private function store(array $ids): void
    {
        $this->session->put(self::SESSION_KEY, array_values($ids));
        $this->vehicles = null;
    }
}
