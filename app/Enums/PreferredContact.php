<?php

namespace App\Enums;

enum PreferredContact: string
{
    case Phone = 'Phone call';
    case WhatsApp = 'WhatsApp';
    case Email = 'Email';

    /**
     * Map the contact-method select (Phone / WhatsApp / Email) to a case.
     */
    public static function fromChoice(?string $choice): self
    {
        return match ($choice) {
            'WhatsApp' => self::WhatsApp,
            'Email' => self::Email,
            default => self::Phone,
        };
    }
}
