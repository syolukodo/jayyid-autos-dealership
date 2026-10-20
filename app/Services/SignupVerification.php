<?php

namespace App\Services;

use App\Mail\VerificationCode;
use App\Support\TransactionalMail;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Hash;

/**
 * Sign-up email verification. The pending account (with only a password
 * hash) is parked in the session until the emailed six-digit code is confirmed.
 */
class SignupVerification
{
    private const SESSION_KEY = 'pending_signup';

    public function __construct(private readonly Session $session) {}

    /**
     * Park a new account and email its first code. False if the email could not be sent.
     *
     * @param  array{name: string, email: string, phone: string, password: string}  $account
     */
    public function start(array $account): bool
    {
        $this->session->put(self::SESSION_KEY, [
            'name' => $account['name'],
            'email' => mb_strtolower($account['email']),
            'phone' => $account['phone'],
            'password_hash' => Hash::make($account['password']),
        ]);

        if ($this->sendCode()) {
            return true;
        }

        $this->forget();

        return false;
    }

    /**
     * @return array{name: string, email: string, phone: string, password_hash: string, code_hash?: string, expires?: int, attempts?: int, sent_at?: int}|null
     */
    public function pending(): ?array
    {
        $pending = $this->session->get(self::SESSION_KEY);

        return is_array($pending) ? $pending : null;
    }

    public function sendCode(): bool
    {
        $pending = $this->pending();
        if ($pending === null) {
            return false;
        }

        $code = (string) random_int(100000, 999999);
        $minutes = (int) config('showroom.signup_verification.ttl_minutes');

        $this->session->put(self::SESSION_KEY, array_merge($pending, [
            'code_hash' => Hash::make($code),
            'expires' => now()->addMinutes($minutes)->getTimestamp(),
            'attempts' => 0,
            'sent_at' => now()->getTimestamp(),
        ]));

        return TransactionalMail::send($pending['email'], new VerificationCode($code, $minutes));
    }

    /**
     * Seconds the visitor must wait before another code may be sent.
     */
    public function resendWait(): int
    {
        $sentAt = (int) ($this->pending()['sent_at'] ?? 0);

        return max(0, (int) config('showroom.signup_verification.resend_wait_seconds') - (now()->getTimestamp() - $sentAt));
    }

    /**
     * Null when the code is right, otherwise the message to show.
     */
    public function check(string $code): ?string
    {
        $pending = $this->pending();

        if ($pending === null || ! isset($pending['code_hash'])) {
            return 'Your verification session has ended. Please request a new code.';
        }
        if (now()->getTimestamp() > (int) ($pending['expires'] ?? 0)) {
            return 'That code has expired. Please request a new one.';
        }
        if ((int) ($pending['attempts'] ?? 0) >= (int) config('showroom.signup_verification.max_attempts')) {
            return 'Too many incorrect attempts. Please request a new code.';
        }
        if (! Hash::check($code, $pending['code_hash'])) {
            $pending['attempts'] = (int) ($pending['attempts'] ?? 0) + 1;
            $this->session->put(self::SESSION_KEY, $pending);

            return 'That code is incorrect. Please check the email and try again.';
        }

        return null;
    }

    public function forget(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }
}
