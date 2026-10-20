<?php

namespace App\Models;

use App\Enums\InstalmentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $schedule_id
 * @property int $sequence
 * @property Carbon $due_date
 * @property int $amount
 * @property InstalmentStatus $status
 */
class PaymentInstalment extends Model
{
    protected $fillable = ['schedule_id', 'sequence', 'due_date', 'amount', 'status', 'paid_at'];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'due_date' => 'date',
            'amount' => 'integer',
            'status' => InstalmentStatus::class,
            'paid_at' => 'datetime',
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(PaymentSchedule::class, 'schedule_id');
    }

    /**
     * An overdue instalment is still the one to pay next, so customers see it as Due.
     */
    public function displayStatus(): InstalmentStatus
    {
        return $this->status === InstalmentStatus::Overdue ? InstalmentStatus::Due : $this->status;
    }
}
