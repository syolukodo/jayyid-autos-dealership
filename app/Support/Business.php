<?php

namespace App\Support;

use App\Services\SettingsRepository;

/**
 * Business contact details shown in the footer, contact page and emails:
 * config('showroom.business') defaults, overridden from Admin > Settings.
 */
final class Business
{
    /**
     * @param  list<string>  $phones
     */
    public function __construct(
        public readonly string $name,
        public readonly string $tagline,
        public readonly array $phones,
        public readonly string $email,
        public readonly string $address,
        public readonly string $hours,
        public readonly string $hoursShort,
        public readonly string $whatsapp,
    ) {}

    public static function fromSettings(SettingsRepository $settings): self
    {
        $defaults = config('showroom.business');

        $phones = $defaults['phones'];
        $whatsapp = $defaults['whatsapp'];
        $phone = $settings->get('business_phone', $phones[0]);
        if ($phone !== $phones[0]) {
            $phones[0] = $phone;
            $digits = preg_replace('/\D/', '', $phone) ?? '';
            $whatsapp = 'https://wa.me/'.(str_starts_with($digits, '0') ? '234'.substr($digits, 1) : $digits);
        }

        $hours = $settings->get('business_hours', $defaults['hours']);

        return new self(
            name: $settings->get('business_name', $defaults['name']),
            tagline: $defaults['tagline'],
            phones: $phones,
            email: $settings->get('business_email', $defaults['email']),
            address: $settings->get('business_address', $defaults['address']),
            hours: $hours,
            hoursShort: $hours === $defaults['hours'] ? $defaults['hours_short'] : $hours,
            whatsapp: $whatsapp,
        );
    }

    public function primaryPhone(): string
    {
        return $this->phones[0];
    }

    /**
     * A phone number as a tel: URI target, e.g. "+2348034567890".
     */
    public static function dialable(string $phone): string
    {
        return preg_replace('/[^\d+]/', '', $phone) ?? '';
    }
}
