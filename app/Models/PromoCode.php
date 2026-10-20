<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\Enums\PromoTarget;
use Database\Factories\PromoCodeFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A checkout discount code, scoped to all vehicles, a brand, a category or a list of vehicles.
 *
 * @property int $id
 * @property string $code
 * @property string $title
 * @property DiscountType $discount_type
 * @property float $discount_value
 * @property float $min_order_amount
 * @property PromoTarget $target_type
 * @property ?string $target_brand
 * @property ?string $target_category
 * @property ?int $usage_limit
 * @property int $times_used
 * @property ?int $usage_limit_per_user
 * @property ?Carbon $start_date
 * @property ?Carbon $expiry_date
 * @property bool $is_active
 */
class PromoCode extends Model
{
    /** @use HasFactory<PromoCodeFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'code', 'title', 'discount_type', 'discount_value', 'min_order_amount', 'target_type', 'target_brand',
        'target_category', 'usage_limit', 'usage_limit_per_user', 'start_date', 'expiry_date', 'is_active',
    ];

    protected $attributes = [
        'discount_type' => 'fixed',
        'target_type' => 'all',
        'min_order_amount' => 0,
        'times_used' => 0,
        'usage_limit_per_user' => 1,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'discount_type' => DiscountType::class,
            'target_type' => PromoTarget::class,
            'discount_value' => 'float',
            'min_order_amount' => 'float',
            'usage_limit' => 'integer',
            'times_used' => 'integer',
            'usage_limit_per_user' => 'integer',
            'start_date' => 'datetime',
            'expiry_date' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    protected function code(): Attribute
    {
        return Attribute::set(fn (string $value) => mb_strtoupper(trim($value)));
    }

    public function vehicles(): BelongsToMany
    {
        return $this->belongsToMany(Vehicle::class, 'promo_code_vehicles');
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(PromoCodeRedemption::class);
    }

    public static function findByCode(string $code): ?self
    {
        $code = mb_strtoupper(trim($code));

        return $code === '' ? null : static::query()->where('code', $code)->first();
    }

    public function hasStarted(): bool
    {
        return $this->start_date === null || $this->start_date->isPast();
    }

    public function hasExpired(): bool
    {
        return $this->expiry_date !== null && $this->expiry_date->isPast();
    }

    // -------------------------------------------------------------- display

    /**
     * "10% off" or "₦500,000 off".
     */
    public function valueLabel(): string
    {
        if ($this->discount_type === DiscountType::Percentage) {
            return rtrim(rtrim(number_format($this->discount_value, 2, '.', ''), '0'), '.').'% off';
        }

        return naira($this->discount_value).' off';
    }

    public function scopeLabel(): string
    {
        return match ($this->target_type) {
            PromoTarget::Brand => 'Brand: '.($this->target_brand ?: 'Any'),
            PromoTarget::Category => 'Category: '.($this->target_category ?: 'Any'),
            PromoTarget::SpecificVehicles => 'Selected vehicles',
            PromoTarget::All => 'All vehicles',
        };
    }

    public function statusLabel(): string
    {
        return match (true) {
            ! $this->is_active => 'Inactive',
            ! $this->hasStarted() => 'Scheduled',
            $this->hasExpired() => 'Expired',
            default => 'Active',
        };
    }

    public function statusPill(): string
    {
        return match ($this->statusLabel()) {
            'Inactive' => 'pill pill--wait',
            'Scheduled' => 'pill pill--info',
            'Expired' => 'pill pill--danger',
            default => 'pill pill--positive',
        };
    }
}
