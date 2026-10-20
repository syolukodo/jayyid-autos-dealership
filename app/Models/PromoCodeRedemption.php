<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One successful use of a promo code, recorded once its payment settles.
 */
class PromoCodeRedemption extends Model
{
    public $timestamps = false;

    protected $fillable = ['promo_code_id', 'user_id', 'order_id', 'discount_amount', 'redeemed_at'];

    protected function casts(): array
    {
        return [
            'discount_amount' => 'integer',
            'redeemed_at' => 'datetime',
        ];
    }

    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'order_id');
    }
}
