<?php

namespace App\Models;

use App\Enums\InquiryStatus;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Enums\StaffModule;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Customers, staff and administrators share this table; `role` decides the portal.
 *
 * @property int $id
 * @property string $full_name
 * @property string $email
 * @property ?string $phone_number
 * @property ?string $address
 * @property ?string $avatar data URI of the profile photo
 * @property ?string $password
 * @property UserRole $role
 * @property UserStatus $status
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    protected $fillable = [
        'full_name',
        'email',
        'phone_number',
        'address',
        'avatar',
        'password',
        'role',
        'status',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'invite_token_hash',
    ];

    protected $attributes = [
        'role' => 'customer',
        'status' => 'Active',
    ];

    protected function casts(): array
    {
        return [
            'role' => UserRole::class,
            'status' => UserStatus::class,
            'email_verified_at' => 'datetime',
            'invite_expires_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * The legacy schema has no remember_token column, so "remember me" is not offered.
     */
    public function getRememberTokenName(): string
    {
        return '';
    }

    protected function email(): Attribute
    {
        return Attribute::set(fn (string $value) => mb_strtolower(trim($value)));
    }

    // ---------------------------------------------------------------- roles

    public function isCustomer(): bool
    {
        return $this->role === UserRole::Customer;
    }

    public function isStaff(): bool
    {
        return $this->role->isStaff();
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function canAccessModule(StaffModule $module): bool
    {
        return $this->isStaff() && $this->role->canAccess($module);
    }

    /**
     * First back-office page this staff member may open.
     */
    public function staffLandingRoute(): ?string
    {
        foreach (StaffModule::cases() as $module) {
            if ($this->canAccessModule($module)) {
                return $module->routeName();
            }
        }

        return null;
    }

    /**
     * Staff accounts have no password until the invite link is used.
     */
    public function hasPendingInvite(): bool
    {
        return $this->password === null;
    }

    public function scopeCustomers(Builder $query): void
    {
        $query->where('role', UserRole::Customer);
    }

    public function scopeStaff(Builder $query): void
    {
        $query->where('role', '<>', UserRole::Customer);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('status', UserStatus::Active);
    }

    // -------------------------------------------------------------- display

    public function firstName(): string
    {
        $parts = preg_split('/\s+/', trim($this->full_name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return $parts[0] ?? 'Customer';
    }

    public function initial(): string
    {
        return mb_strtoupper(mb_substr(trim($this->full_name) ?: 'A', 0, 1));
    }

    /**
     * Customer reference shown on the portal, e.g. JA-CUS-00002.
     */
    public function customerReference(): string
    {
        return sprintf('JA-CUS-%05d', $this->id);
    }

    public function isVerified(): bool
    {
        return $this->email_verified_at !== null;
    }

    // -------------------------------------------------------- relationships

    public function savedVehicles(): BelongsToMany
    {
        return $this->belongsToMany(Vehicle::class, 'saved_vehicles')
            ->withPivot('created_at')
            ->orderByPivot('created_at')
            ->orderBy('vehicles.id');
    }

    public function paymentSchedules(): HasMany
    {
        return $this->hasMany(PaymentSchedule::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function portalNotifications(): HasMany
    {
        return $this->hasMany(UserNotification::class);
    }

    /**
     * Inquiries sent while signed in, plus those sent earlier with the same email.
     *
     * @return Builder<Inquiry>
     */
    public function inquiries(): Builder
    {
        return Inquiry::query()
            ->where(fn (Builder $query) => $query->where('user_id', $this->id)->orWhere('client_email', $this->email))
            ->latest()
            ->orderByDesc('id');
    }

    public function openInquiriesCount(): int
    {
        return $this->inquiries()
            ->whereIn('status', [InquiryStatus::New, InquiryStatus::Contacted])
            ->count();
    }

    /**
     * The schedule shown on the portal: the newest still-open one, else the newest.
     */
    public function currentSchedule(): ?PaymentSchedule
    {
        return $this->paymentSchedules()
            ->with(['vehicle', 'instalments'])
            ->orderByRaw("status = 'Completed'")
            ->latest()
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Vehicles reserved with a paid deposit.
     */
    public function reservationsCount(): int
    {
        return $this->payments()
            ->where('purpose', PaymentPurpose::Deposit)
            ->where('status', PaymentStatus::Paid)
            ->distinct()
            ->count('vehicle_id');
    }
}
