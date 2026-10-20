<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Checkout ledger: one row per gateway attempt, verified server-side before it is marked paid.
 *
 * @property int $id
 * @property string $reference
 * @property ?int $user_id
 * @property ?int $vehicle_id
 * @property ?int $schedule_id
 * @property PaymentPurpose $purpose
 * @property PaymentMethod $method
 * @property int $amount_kobo
 * @property ?int $outstanding
 * @property string $vehicle_title
 * @property int $vehicle_price
 * @property string $customer_name
 * @property string $customer_email
 * @property PaymentStatus $status
 * @property ?string $gateway_reference PayPal order id
 * @property ?string $promo_code
 * @property ?int $promo_discount
 * @property ?int $delivery_fee null for showroom pickup
 * @property ?string $delivery_state
 * @property ?string $delivery_city
 * @property ?string $delivery_address
 * @property ?list<int> $vehicle_ids vehicles a selection payment reserves
 * @property ?string $gateway_currency currency PayPal charged in (null: charged in naira)
 * @property ?int $gateway_amount_minor amount charged in that currency's minor unit
 * @property ?float $exchange_rate naira per 1 unit of gateway_currency
 */
class Payment extends Model
{
    protected $fillable = [
        'reference', 'user_id', 'vehicle_id', 'schedule_id', 'purpose', 'method', 'amount_kobo', 'outstanding',
        'vehicle_title', 'vehicle_price', 'customer_name', 'customer_email', 'status', 'gateway_status',
        'gateway_message', 'gateway_reference', 'promo_code', 'promo_discount', 'channel', 'paid_at',
        'delivery_fee', 'delivery_state', 'delivery_city', 'delivery_address', 'vehicle_ids',
        'gateway_currency', 'gateway_amount_minor', 'exchange_rate',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'purpose' => PaymentPurpose::class,
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'amount_kobo' => 'integer',
            'outstanding' => 'integer',
            'vehicle_price' => 'integer',
            'promo_discount' => 'integer',
            'delivery_fee' => 'integer',
            'vehicle_ids' => 'array',
            'gateway_amount_minor' => 'integer',
            'exchange_rate' => 'float',
            'paid_at' => 'datetime',
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

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(PaymentSchedule::class, 'schedule_id');
    }

    public function amount(): int
    {
        return intdiv($this->amount_kobo, 100);
    }

    public function isPaid(): bool
    {
        return $this->status === PaymentStatus::Paid;
    }

    public function belongsToUser(User $user): bool
    {
        return $this->customer_email === $user->email;
    }

    /**
     * Payments made by this customer, matched on account or on the email used at checkout.
     *
     * @param  Builder<Payment>  $query
     */
    public function scopeMadeBy(Builder $query, User $user): void
    {
        $query->where(fn (Builder $q) => $q->where('user_id', $user->id)->orWhere('customer_email', $user->email));
    }

    /**
     * @param  Builder<Payment>  $query
     */
    public function scopePaidBy(Builder $query, User $user): void
    {
        $query->madeBy($user)->where('status', PaymentStatus::Paid);
    }

    /**
     * Charged in another currency (PayPal), e.g. "$1,234.57".
     */
    public function chargedInForeignCurrency(): bool
    {
        return $this->gateway_currency !== null && $this->gateway_currency !== 'NGN' && $this->gateway_amount_minor !== null;
    }

    public function chargedLabel(): string
    {
        return currency_label((string) $this->gateway_currency, (int) $this->gateway_amount_minor);
    }

    /**
     * File name for the receipt PDF (and the receipt page title), e.g. Receipt-JA-PAY-4B75AF8C3C3C-Jayyid-Autos.
     */
    public function receiptFileName(string $businessName): string
    {
        return 'Receipt-'.$this->reference.'-'.trim((string) preg_replace('/[^A-Za-z0-9]+/', '-', $businessName), '-');
    }

    /**
     * Receipt summary rows (label => value), shared by the receipt page and its PDF.
     *
     * @return array<string, string>
     */
    public function receiptDetails(): array
    {
        $details = [
            'Date paid' => (string) $this->paidOn()?->format('d M Y, g:i A'),
            'Payment method' => $this->method->label().($this->channel ? ' ('.ucfirst(str_replace('_', ' ', $this->channel)).')' : ''),
            'Payment for' => $this->purpose->label(),
        ];

        if ($this->chargedInForeignCurrency()) {
            $details['Charged by PayPal'] = $this->chargedLabel().' '.$this->gateway_currency
                .' (₦'.number_format((float) $this->exchange_rate, 2).' = '.currency_label((string) $this->gateway_currency, 100).')';
        }

        if ($this->purpose === PaymentPurpose::Cart) {
            $details['Fulfilment'] = $this->isDelivery() ? 'Delivery to '.$this->deliveryDestination() : 'Showroom pickup';
        }

        return $details;
    }

    /**
     * Receipt line items as [label, amount] pairs, shared by the receipt page and its PDF.
     *
     * @return list<array{0: string, 1: string}>
     */
    public function receiptLines(): array
    {
        $discount = (int) $this->promo_discount;
        $lines = [];

        if ($this->purpose === PaymentPurpose::Cart) {
            $lines[] = ['Vehicle subtotal', naira($this->vehicle_price)];
            // Documentation and tax are folded into the charged total; delivery is stored separately.
            $delivery = (int) $this->delivery_fee;
            $fees = $this->amount() + $discount - $this->vehicle_price - $delivery;
            if ($fees > 0) {
                $lines[] = [$this->isDelivery() ? 'Documentation & tax' : 'Documentation, tax & fees', naira($fees)];
            }
            if ($this->isDelivery()) {
                $lines[] = ['Doorstep delivery to '.$this->delivery_state, naira($delivery)];
            }
        } else {
            $lines[] = ['Vehicle price', naira($this->vehicle_price)];
        }

        if ($discount > 0) {
            $lines[] = ['Promo discount ('.$this->promo_code.')', '- '.naira($discount)];
        }

        return $lines;
    }

    public function isDelivery(): bool
    {
        return $this->delivery_fee !== null;
    }

    public function deliveryDestination(): string
    {
        return implode(', ', array_filter([$this->delivery_address, $this->delivery_city, $this->delivery_state]));
    }

    /**
     * Gateway confirmation time, falling back to when the row was last updated.
     */
    public function paidOn(): ?CarbonInterface
    {
        return $this->paid_at ?? $this->updated_at;
    }

    /**
     * Balance left after this payment, for the receipt.
     */
    public function outstandingAfter(): int
    {
        return $this->outstanding ?? max(0, $this->vehicle_price - $this->amount());
    }
}
