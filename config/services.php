<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | Credentials for the outbound integrations. Every key is optional: a
    | missing gateway key keeps that checkout tab in preview mode, and a
    | missing Resend key skips email verification and shows staff invite
    | links on screen instead of emailing them.
    |
    */

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'paystack' => [
        'public_key' => env('PAYSTACK_PUBLIC_KEY'),
        'secret_key' => env('PAYSTACK_SECRET_KEY'),
        'base_url' => env('PAYSTACK_PAYMENT_URL', 'https://api.paystack.co'),
        'currency' => strtoupper((string) env('PAYSTACK_CURRENCY', 'NGN')),
        // Seconds a generated bank-transfer account stays open.
        'transfer_ttl' => 1800,
    ],

    'paypal' => [
        'mode' => env('PAYPAL_MODE', 'sandbox') === 'live' ? 'live' : 'sandbox',
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'secret' => env('PAYPAL_SECRET'),
        // PayPal does not settle in naira: charges convert at the rate set in Admin > Settings.
        'currency' => strtoupper((string) env('PAYPAL_CURRENCY', 'USD')),
        // Naira per 1 unit of the currency above; the starting value until staff set one.
        'exchange_rate' => env('PAYPAL_EXCHANGE_RATE'),
        'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
    ],

];
