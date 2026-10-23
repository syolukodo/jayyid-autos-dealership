<?php

namespace App\Support;

use App\Enums\VehicleCategory;
use App\Enums\VehicleCondition;
use App\Models\Vehicle;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Indexable stock pages for the searches Nigerian buyers make:
 * "Tokunbo cars for sale in Lagos", "Toyota cars for sale in Lagos",
 * "SUVs for sale in Lagos". Each lives at /cars-for-sale/{slug} with its own
 * heading, title and description built from live stock figures.
 */
final class InventoryLanding
{
    private const CONDITIONS = [
        'tokunbo' => VehicleCondition::Tokunbo,
        'brand-new' => VehicleCondition::BrandNew,
    ];

    private const CATEGORIES = [
        'suv' => VehicleCategory::SUV,
        'sedan' => VehicleCategory::Sedan,
        'pickup' => VehicleCategory::Pickup,
        'commercial' => VehicleCategory::Commercial,
    ];

    /**
     * @param  array{condition?: string, make?: string, body?: string}  $filters
     */
    private function __construct(
        public readonly string $slug,
        public readonly string $label,
        public readonly array $filters,
        private readonly string $kind,
    ) {}

    public static function resolve(string $slug): ?self
    {
        $slug = Str::lower($slug);

        if (isset(self::CONDITIONS[$slug])) {
            $condition = self::CONDITIONS[$slug];

            return new self($slug, $condition->value, ['condition' => $condition->value], 'condition');
        }

        if (isset(self::CATEGORIES[$slug])) {
            $category = self::CATEGORIES[$slug];

            return new self($slug, $category->plural(), ['body' => $category->value], 'category');
        }

        $make = Vehicle::query()->distinct()->pluck('make')->first(fn (string $make) => Str::slug($make) === $slug);

        return $make !== null ? new self($slug, $make, ['make' => $make], 'make') : null;
    }

    public static function forMake(string $make): self
    {
        return new self(Str::slug($make), $make, ['make' => $make], 'make');
    }

    /**
     * The landing page that exactly matches a single-filter inventory query, if any.
     *
     * @param  array<string, string>  $filters
     */
    public static function matching(array $filters): ?self
    {
        $active = array_filter($filters, fn ($value) => $value !== '');
        if (count($active) !== 1) {
            return null;
        }

        $value = reset($active);

        return match (key($active)) {
            'condition' => ($slug = array_search(VehicleCondition::tryFrom($value), self::CONDITIONS, true)) !== false ? self::resolve($slug) : null,
            'body' => ($slug = array_search(VehicleCategory::tryFrom($value), self::CATEGORIES, true)) !== false ? self::resolve($slug) : null,
            'make' => self::resolve(Str::slug($value)),
            default => null,
        };
    }

    /**
     * Every landing page that currently has stock (for the sitemap and footer links).
     *
     * @return Collection<int, self>
     */
    public static function all(): Collection
    {
        $stock = Vehicle::query()->inStock()->get(['make', 'condition', 'category']);

        return collect()
            ->merge(collect(self::CONDITIONS)->filter(fn ($c) => $stock->contains('condition', $c))->keys()->map(fn ($slug) => self::resolve($slug)))
            ->merge(collect(self::CATEGORIES)->filter(fn ($c) => $stock->contains('category', $c))->keys()->map(fn ($slug) => self::resolve($slug)))
            ->merge($stock->pluck('make')->unique()->sort()->values()->map(fn (string $make) => self::forMake($make)))
            ->values();
    }

    public function url(): string
    {
        return route('inventory.landing', $this->slug);
    }

    public function heading(): string
    {
        return match ($this->kind) {
            'condition' => "{$this->label} Cars for Sale in Lagos",
            'category' => "{$this->label} for Sale in Lagos",
            default => "{$this->label} Cars for Sale in Lagos",
        };
    }

    /**
     * "Page | Jayyid Autos - search phrase", e.g. "Toyota Cars | Jayyid Autos - Tokunbo & Brand New Toyota for Sale in Lagos, Nigeria".
     *
     * @param  string  $pageSuffix  e.g. " - Page 2", placed after the page name
     */
    public function title(string $pageSuffix = ''): string
    {
        [$page, $phrase] = match ($this->kind) {
            'condition' => $this->label === VehicleCondition::Tokunbo->value
                ? ['Tokunbo Cars', 'Tokunbo (Foreign Used) Cars for Sale in Lagos, Nigeria']
                : ['Brand New Cars', 'Brand New Cars for Sale in Lagos, Nigeria'],
            'category' => [$this->label, "Tokunbo & Brand New {$this->label} for Sale in Lagos, Nigeria"],
            default => ["{$this->label} Cars", "Tokunbo & Brand New {$this->label} for Sale in Lagos, Nigeria"],
        };

        return $page.$pageSuffix.' | Jayyid Autos - '.$phrase;
    }

    /**
     * Unique copy from live stock: count and starting price.
     */
    public function description(int $count, ?int $fromPrice): string
    {
        $subject = match ($this->kind) {
            'condition' => $this->label === VehicleCondition::Tokunbo->value ? 'Tokunbo (foreign used) cars' : 'brand new cars',
            'category' => Str::lower($this->label),
            default => "{$this->label} cars",
        };
        $from = $fromPrice ? ' from '.naira($fromPrice) : '';

        return Str::limit(
            "Browse {$count} inspected {$subject} for sale{$from} at Jayyid Autos, Ikeja, Lagos. "
            .'Verified mileage, honest prices and 0% interest payment plans.',
            158
        );
    }

    public function lead(): string
    {
        return match ($this->kind) {
            'condition' => $this->label === VehicleCondition::Tokunbo->value
                ? 'Foreign used (Tokunbo) vehicles imported clean, inspected on arrival and sold with verified mileage. Every unit below is physically at our Ikeja showroom and priced with all charges disclosed.'
                : 'Factory-sealed brand new vehicles with full manufacturer specification. Every unit below is physically at our Ikeja showroom and priced with all charges disclosed.',
            default => "Every {$this->label} unit below is physically at our Ikeja showroom in Lagos, inspected before display and priced with all charges disclosed. Pay in full or spread the balance over up to six months at 0% interest.",
        };
    }
}
