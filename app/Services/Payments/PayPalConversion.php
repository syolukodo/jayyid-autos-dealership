<?php

namespace App\Services\Payments;

use App\Services\SettingsRepository;

/**
 * PayPal does not settle in naira, so a naira charge is converted at the
 * exchange rate staff set in Admin > Settings (falling back to
 * PAYPAL_EXCHANGE_RATE). Without a rate PayPal is unavailable rather than
 * charging at a guessed rate. Amounts round up to the cent.
 */
class PayPalConversion
{
    public function __construct(private readonly SettingsRepository $settings) {}

    public function currency(): string
    {
        return strtoupper((string) config('services.paypal.currency'));
    }

    /**
     * Naira per one unit of the PayPal currency; null when no usable rate is set.
     */
    public function rate(): ?float
    {
        if ($this->currency() === 'NGN') {
            return 1.0;
        }

        $rate = (float) $this->settings->get('paypal_exchange_rate', (string) config('services.paypal.exchange_rate'));

        return $rate > 0 ? $rate : null;
    }

    public function available(): bool
    {
        return $this->rate() !== null;
    }

    public function convert(int $naira): ?PayPalAmount
    {
        $rate = $this->rate();
        if ($rate === null) {
            return null;
        }

        // Round to 6 places first so float noise (e.g. 999.0000001) does not add a cent.
        $minor = (int) ceil(round($naira * 100 / $rate, 6));

        return new PayPalAmount($this->currency(), $minor, $rate);
    }
}
