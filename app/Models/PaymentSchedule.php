<?php

namespace App\Models;

use App\Enums\InstalmentStatus;
use App\Enums\ScheduleStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An instalment plan for one buyer and one vehicle.
 *
 * @property int $id
 * @property string $schedule_code
 * @property int $user_id
 * @property int $vehicle_id
 * @property int $total_price
 * @property int $deposit
 * @property int $total_paid
 * @property int $remaining_balance
 * @property ScheduleStatus $status
 */
class PaymentSchedule extends Model
{
    protected $fillable = [
        'schedule_code', 'user_id', 'vehicle_id', 'total_price', 'deposit', 'deposit_amount',
        'total_paid', 'remaining_balance', 'status', 'paystack_plan_code',
    ];

    protected function casts(): array
    {
        return [
            'total_price' => 'integer',
            'deposit' => 'integer',
            'deposit_amount' => 'integer',
            'total_paid' => 'integer',
            'remaining_balance' => 'integer',
            'status' => ScheduleStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function instalments(): HasMany
    {
        return $this->hasMany(PaymentInstalment::class, 'schedule_id')->orderBy('sequence');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class, 'schedule_id');
    }

    public function progressPercent(): int
    {
        return $this->total_price > 0 ? (int) round($this->total_paid / $this->total_price * 100) : 0;
    }

    /**
     * The next instalment subtotal to settle (before the documentation fee):
     * the first one due, else the first upcoming one.
     */
    public function dueAmount(): int
    {
        $instalments = $this->instalments;

        $next = $instalments->first(fn (PaymentInstalment $i) => $i->displayStatus() === InstalmentStatus::Due)
            ?? $instalments->first(fn (PaymentInstalment $i) => $i->status === InstalmentStatus::Upcoming);

        return $next?->amount ?? 0;
    }

    public function nextDueDate(): ?string
    {
        return $this->instalments
            ->reject(fn (PaymentInstalment $i) => $i->status === InstalmentStatus::Paid)
            ->min('due_date')
            ?->format('d M Y');
    }
}
