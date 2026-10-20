<?php

namespace App\Models;

use App\Enums\FuelType;
use App\Enums\Transmission;
use App\Enums\VehicleCategory;
use App\Enums\VehicleCondition;
use App\Enums\VehicleStatus;
use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $title
 * @property string $make
 * @property string $model
 * @property int $year
 * @property VehicleCondition $condition
 * @property VehicleCategory $category
 * @property int $price whole naira
 * @property ?int $original_price
 * @property ?string $mileage
 * @property Transmission $transmission
 * @property FuelType $fuel_type
 * @property VehicleStatus $status
 * @property bool $is_featured
 * @property ?string $primary_image path under public/
 * @property list<string> $features
 */
class Vehicle extends Model
{
    /** @use HasFactory<VehicleFactory> */
    use HasFactory;

    public const SORTS = [
        'newest' => 'Newest Year First',
        'price-asc' => 'Price: Low to High',
        'price-desc' => 'Price: High to Low',
    ];

    public const BUDGETS = [
        '0-25000000' => 'Under ₦25,000,000',
        '25000000-40000000' => '₦25,000,000 to ₦40,000,000',
        '40000000-60000000' => '₦40,000,000 to ₦60,000,000',
        '60000000-999999999' => 'Above ₦60,000,000',
    ];

    protected $fillable = [
        'vin_number', 'title', 'make', 'model', 'year', 'condition', 'category', 'price', 'original_price',
        'mileage', 'transmission', 'fuel_type', 'engine', 'drive_type', 'colour', 'seats', 'location',
        'description', 'features', 'status', 'is_featured', 'primary_image',
    ];

    protected $attributes = [
        'features' => '[]',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'price' => 'integer',
            'original_price' => 'integer',
            'seats' => 'integer',
            'condition' => VehicleCondition::class,
            'category' => VehicleCategory::class,
            'transmission' => Transmission::class,
            'fuel_type' => FuelType::class,
            'status' => VehicleStatus::class,
            'is_featured' => 'boolean',
            'features' => 'array',
        ];
    }

    /**
     * Only an Available vehicle can be selected, reserved or paid for; Reserved and Sold ones are taken.
     */
    public function isAvailable(): bool
    {
        return $this->status === VehicleStatus::Available;
    }

    public function imageUrl(): string
    {
        return $this->primary_image ? asset($this->primary_image) : '';
    }

    // ------------------------------------------------------------------ SEO

    public function slug(): string
    {
        return Str::slug($this->title) ?: 'vehicle';
    }

    /**
     * URLs carry the title for search engines: /vehicles/5-2022-toyota-land-cruiser-prado.
     */
    public function getRouteKey(): string
    {
        return $this->id.'-'.$this->slug();
    }

    /**
     * Only the leading id matters, so old links (/vehicles/5) and renamed titles still resolve.
     */
    public function resolveRouteBinding($value, $field = null): ?self
    {
        if ($field !== null) {
            return parent::resolveRouteBinding($value, $field);
        }

        return preg_match('/^(\d+)/', (string) $value, $match) ? static::query()->find((int) $match[1]) : null;
    }

    public function seoTitle(): string
    {
        $verb = $this->status === VehicleStatus::Sold ? 'Sold' : 'for Sale';

        return "{$this->title} {$verb} in Lagos, Nigeria - ".naira($this->price);
    }

    public function seoDescription(): string
    {
        $specs = collect([$this->condition->value, $this->mileage, $this->transmission->value, $this->fuel_type->value, $this->colour])
            ->filter()->implode(', ');

        return Str::limit(
            "{$this->title} ({$specs}) for ".naira($this->price).' at our Ikeja, Lagos showroom. '
            .'Inspected, verified mileage, honest pricing and 0% interest payment plans.',
            158
        );
    }

    /**
     * Stock reference shown to buyers.
     */
    public function stockReference(): string
    {
        return mb_strtoupper((string) $this->id);
    }

    public function discountPercent(): int
    {
        if (! $this->original_price || $this->original_price <= $this->price) {
            return 0;
        }

        return (int) round((1 - $this->price / $this->original_price) * 100);
    }

    public function mileageValue(): int
    {
        return (int) preg_replace('/\D/', '', (string) $this->mileage);
    }

    public function depositAmount(): int
    {
        return (int) round($this->price * config('showroom.deposit_rate'));
    }

    // --------------------------------------------------------------- scopes

    public function scopeInStock(Builder $query): void
    {
        $query->where('status', '<>', VehicleStatus::Sold);
    }

    /**
     * Newest model year first, featured units ahead within a year.
     */
    public function scopeNewestFirst(Builder $query): void
    {
        $query->orderByDesc('year')->orderByDesc('is_featured')->orderBy('id');
    }

    /**
     * @param  array{condition?: ?string, make?: ?string, body?: ?string, budget?: ?string}  $filters
     */
    public function scopeFilter(Builder $query, array $filters): void
    {
        $query
            ->when($filters['condition'] ?? null, fn (Builder $q, string $v) => $q->where('condition', $v))
            ->when($filters['make'] ?? null, fn (Builder $q, string $v) => $q->where('make', $v))
            ->when($filters['body'] ?? null, fn (Builder $q, string $v) => $q->where('category', $v));

        if (preg_match('/^(\d+)-(\d+)$/', (string) ($filters['budget'] ?? ''), $range)) {
            $query->whereBetween('price', [(int) $range[1], (int) $range[2]]);
        }
    }

    public function scopeSortBy(Builder $query, string $sort): void
    {
        match ($sort) {
            'price-asc' => $query->orderBy('price')->orderBy('id'),
            'price-desc' => $query->orderByDesc('price')->orderBy('id'),
            default => $query->newestFirst(),
        };
    }

    /**
     * Shape consumed by the live inventory filter (public/assets/js/search-filter.js).
     *
     * @return array<string, mixed>
     */
    public function toCatalogueArray(): array
    {
        return [
            'id' => (string) $this->id,
            'title' => $this->title,
            'condition' => $this->condition->value,
            'category' => $this->category->value,
            'make' => $this->make,
            'model' => $this->model,
            'year' => $this->year,
            'price' => $this->price,
            'original_price' => $this->original_price,
            'mileage' => (string) $this->mileage,
            'transmission' => $this->transmission->value,
            'fuel' => $this->fuel_type->value,
            'engine' => (string) $this->engine,
            'drive' => (string) $this->drive_type,
            'colour' => (string) $this->colour,
            'seats' => $this->seats,
            'image' => $this->imageUrl(),
            'url' => route('vehicles.show', $this),
            'featured' => $this->is_featured,
            'status' => $this->status->value,
            'added' => $this->created_at?->toDateString() ?? '',
            'location' => (string) $this->location,
            'description' => (string) $this->description,
            'features' => array_values($this->features ?? []),
        ];
    }

    /**
     * Shape used by the selection drawer, its JSON endpoint and the add-to-selection buttons.
     *
     * @return array<string, mixed>
     */
    public function toSelectionArray(): array
    {
        return [
            'id' => (string) $this->id,
            'title' => $this->title,
            'condition' => $this->condition->value,
            'mileage' => (string) $this->mileage,
            'colour' => (string) $this->colour,
            'price' => $this->price,
            'price_label' => naira($this->price),
            'image' => $this->imageUrl(),
            'url' => route('vehicles.show', $this),
            'available' => $this->isAvailable(),
        ];
    }
}
