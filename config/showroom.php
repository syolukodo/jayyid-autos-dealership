<?php

/*
|--------------------------------------------------------------------------
| Showroom business rules
|--------------------------------------------------------------------------
|
| Fees, rates and limits used by checkout, payment plans and the back
| office. Amounts are whole naira.
|
*/

return [

    'fees' => [
        // One-time documentation & inspection processing fee.
        'documentation' => 250_000,
    ],

    /*
    | Doorstep flatbed delivery, priced per vehicle: the destination state's
    | zone rate (distance band from the Lagos showroom) scaled by the body
    | type's size factor, rounded to the nearest ₦1,000. Rates and factors
    | here are defaults; Admin > Settings overrides them.
    */
    'delivery' => [
        'zones' => [
            'lagos' => ['label' => 'Lagos', 'rate' => 75_000, 'states' => ['Lagos']],
            'south_west' => ['label' => 'South West', 'rate' => 150_000, 'states' => ['Ekiti', 'Ogun', 'Ondo', 'Osun', 'Oyo']],
            'south' => ['label' => 'South South & South East', 'rate' => 300_000, 'states' => [
                'Abia', 'Akwa Ibom', 'Anambra', 'Bayelsa', 'Cross River', 'Delta', 'Ebonyi', 'Edo', 'Enugu', 'Imo', 'Rivers',
            ]],
            'north_central' => ['label' => 'FCT & North Central', 'rate' => 350_000, 'states' => [
                'Benue', 'FCT - Abuja', 'Kogi', 'Kwara', 'Nasarawa', 'Niger', 'Plateau',
            ]],
            'north' => ['label' => 'North East & North West', 'rate' => 500_000, 'states' => [
                'Adamawa', 'Bauchi', 'Borno', 'Gombe', 'Jigawa', 'Kaduna', 'Kano', 'Katsina', 'Kebbi', 'Sokoto', 'Taraba', 'Yobe', 'Zamfara',
            ]],
        ],
        // Percent of the zone rate by body type: larger vehicles take more carrier space.
        'size_factors' => ['Sedan' => 100, 'SUV' => 125, 'Pickup' => 125, 'Commercial' => 160],
    ],

    // Estimated tax & registration share of the vehicle subtotal.
    'tax_rate' => 0.075,

    // Minimum deposit share of a vehicle's price.
    'deposit_rate' => 0.3,

    // Payment plans: balance spread over at most this many monthly instalments.
    'max_instalments' => 6,

    'inventory_per_page' => 12,

    'staff_invite_ttl_hours' => 48,

    'signup_verification' => [
        'ttl_minutes' => 10,
        'max_attempts' => 5,
        'resend_wait_seconds' => 60,
    ],

    // Business details shown on the site until they are changed in Admin > Settings.
    'business' => [
        'name' => 'Jayyid Autos',
        'tagline' => 'Brand new and Tokunbo vehicles sold with verified history, honest pricing and ethical payment terms.',
        'phones' => ['+234 803 456 7890', '+234 805 123 4567'],
        'email' => 'sales@jayyidautos.com',
        'address' => '115 Ikeja Way, Lagos',
        'hours' => 'Monday to Saturday: 9:00am to 6:00pm',
        'hours_short' => 'Mon to Sat, 9:00am to 6:00pm',
        'whatsapp' => 'https://wa.me/2348034567890',
    ],

    'advert' => [
        'default_image' => 'assets/images/promo/jayyid-advert.jpg',
    ],

    /*
    |--------------------------------------------------------------------------
    | Search engine optimisation
    |--------------------------------------------------------------------------
    |
    | Nigeria first: pages declare en-NG, prices are NGN, and the structured
    | data describes a Lagos dealership serving Nigeria. International
    | visitors get the same content through the x-default alternate.
    |
    */

    'seo' => [
        'language' => 'en-NG',
        'og_locale' => 'en_NG',
        'country' => 'NG',
        'default_image' => 'assets/images/cars/hero-showroom.jpg',

        // Postal address parts for structured data; the street line comes from Admin > Settings.
        'locality' => 'Ikeja',
        'region' => 'Lagos',
        'region_code' => 'NG-LA',

        // Showroom coordinates (Google Maps > right-click the pin). Defaults to central Ikeja.
        'latitude' => (float) env('SHOWROOM_LATITUDE', 6.6018),
        'longitude' => (float) env('SHOWROOM_LONGITUDE', 3.3515),

        'opening_hours' => [
            ['days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'], 'opens' => '09:00', 'closes' => '18:00'],
        ],

        // Official social profiles (Instagram, Facebook, X, YouTube, Google Business Profile), comma separated.
        'same_as' => array_values(array_filter(array_map('trim', explode(',', (string) env('SHOWROOM_SOCIAL_PROFILES', ''))))),

        'google_site_verification' => env('GOOGLE_SITE_VERIFICATION'),
        'bing_site_verification' => env('BING_SITE_VERIFICATION'),
    ],

];
