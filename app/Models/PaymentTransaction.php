<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A debit applied to a schedule. paystack_reference holds the payment
 * reference (any gateway) and is unique, so re-applying is impossible.
 */
class PaymentTransaction extends Model
{
    protected $fillable = ['schedule_id', 'paystack_reference', 'amount_paid', 'payment_method', 'payment_date', 'receipt_url'];

    protected function casts(): array
    {
        return [
            'amount_paid' => 'integer',
            'payment_date' => 'datetime',
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(PaymentSchedule::class, 'schedule_id');
    }
}
