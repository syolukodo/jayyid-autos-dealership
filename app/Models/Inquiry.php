<?php

namespace App\Models;

use App\Enums\InquiryStatus;
use App\Enums\PreferredContact;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $reference_code
 * @property ?int $user_id
 * @property ?int $vehicle_id
 * @property string $client_name
 * @property string $client_email
 * @property ?string $client_phone
 * @property PreferredContact $preferred_contact
 * @property ?string $subject
 * @property ?string $message
 * @property InquiryStatus $status
 */
class Inquiry extends Model
{
    protected $fillable = [
        'reference_code', 'user_id', 'vehicle_id', 'client_name', 'client_email', 'client_phone',
        'preferred_contact', 'subject', 'message', 'status',
    ];

    protected $attributes = [
        'status' => 'New',
    ];

    protected function casts(): array
    {
        return [
            'preferred_contact' => PreferredContact::class,
            'status' => InquiryStatus::class,
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

    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', [InquiryStatus::New, InquiryStatus::Contacted]);
    }

    public function vehicleTitle(): string
    {
        return $this->vehicle?->title ?? 'General enquiry';
    }
}
