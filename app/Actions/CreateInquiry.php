<?php

namespace App\Actions;

use App\Enums\NotificationType;
use App\Enums\PreferredContact;
use App\Mail\InquiryReceived;
use App\Models\Inquiry;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Notifier;
use App\Services\SettingsRepository;
use App\Support\Business;
use App\Support\TransactionalMail;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;

/**
 * Records an inquiry from the contact page or a vehicle page, alerts the
 * staff bells and (when enabled) emails the sales address.
 */
class CreateInquiry
{
    private const MAX_REFERENCE_ATTEMPTS = 5;

    public function __construct(
        private readonly Notifier $notifier,
        private readonly SettingsRepository $settings,
        private readonly Business $business,
    ) {}

    /**
     * @param  array{name: string, email?: ?string, phone: string, contact_method?: ?string, subject: string, message?: ?string}  $data
     */
    public function __invoke(array $data, ?Vehicle $vehicle, ?User $customer): Inquiry
    {
        $inquiry = $this->insertWithUniqueReference([
            'user_id' => $customer?->id,
            'vehicle_id' => $vehicle?->id,
            'client_name' => $data['name'],
            'client_email' => mb_strtolower((string) ($data['email'] ?: $customer?->email)),
            'client_phone' => $data['phone'],
            'preferred_contact' => PreferredContact::fromChoice($data['contact_method'] ?? null),
            'subject' => Str::limit($data['subject'], 255, ''),
            'message' => ($data['message'] ?? '') !== '' ? $data['message'] : null,
        ]);
        $inquiry->setRelation('vehicle', $vehicle);

        $this->notifier->notifyStaff(
            NotificationType::Inquiry,
            'New inquiry '.$inquiry->reference_code,
            $inquiry->client_name.' - '.$inquiry->subject.($vehicle ? ' ('.$vehicle->title.')' : ''),
            'admin/inquiries',
        );

        if ($this->settings->enabled('notify_inquiries')) {
            TransactionalMail::send($this->business->email, new InquiryReceived($inquiry));
        }

        return $inquiry;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function insertWithUniqueReference(array $attributes): Inquiry
    {
        for ($attempt = 1; ; $attempt++) {
            try {
                return Inquiry::query()->create($attributes + [
                    'reference_code' => 'JA-INQ-'.mb_strtoupper(bin2hex(random_bytes(3))),
                ]);
            } catch (UniqueConstraintViolationException $exception) {
                if ($attempt >= self::MAX_REFERENCE_ATTEMPTS) {
                    throw $exception;
                }
            }
        }
    }
}
