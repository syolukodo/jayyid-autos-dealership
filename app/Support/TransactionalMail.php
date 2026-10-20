<?php

namespace App\Support;

use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends customer/staff email only when a real mailer is configured. With the
 * log or array mailer the site still works: sign-up skips email verification
 * and staff invite links are shown on screen instead.
 */
final class TransactionalMail
{
    public static function enabled(): bool
    {
        return ! in_array(config('mail.default'), ['log', 'array'], true);
    }

    /**
     * Send now; returns false (and logs) when mail is disabled or delivery fails.
     */
    public static function send(string $to, Mailable $mailable): bool
    {
        if (! self::enabled()) {
            return false;
        }

        try {
            Mail::to($to)->send($mailable);

            return true;
        } catch (Throwable $exception) {
            Log::error('Transactional email failed', ['to' => $to, 'mailable' => $mailable::class, 'exception' => $exception]);

            return false;
        }
    }
}
