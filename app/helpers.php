<?php

if (! function_exists('naira')) {
    /**
     * Format a whole-naira amount, e.g. 65000000 -> "₦65,000,000".
     */
    function naira(int|float|string|null $amount): string
    {
        return '₦'.number_format((float) $amount);
    }
}

if (! function_exists('currency_label')) {
    /**
     * Format an amount in minor units (cents) for a currency, e.g. ('USD', 123456) -> "$1,234.56".
     */
    function currency_label(string $currency, int $minor): string
    {
        $symbols = ['USD' => '$', 'GBP' => '£', 'EUR' => '€', 'NGN' => '₦'];
        $amount = number_format($minor / 100, 2);

        return isset($symbols[$currency]) ? $symbols[$currency].$amount : $amount.' '.$currency;
    }
}

if (! function_exists('versioned_asset')) {
    /**
     * asset() with a ?v=<mtime> cache buster, so CSS/JS can be cached for a
     * year by browsers and CDNs yet refresh the moment the file changes.
     */
    function versioned_asset(string $path): string
    {
        $file = public_path($path);

        return asset($path).(is_file($file) ? '?v='.filemtime($file) : '');
    }
}

if (! function_exists('initials')) {
    /**
     * Up to two upper-case initials for an avatar, e.g. "Ngozi Eze" -> "NE".
     */
    function initials(?string $name, string $fallback = 'A'): string
    {
        $parts = preg_split('/\s+/', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($parts === []) {
            return $fallback;
        }

        $letters = mb_strtoupper(mb_substr($parts[0], 0, 1));
        if (isset($parts[1])) {
            $letters .= mb_strtoupper(mb_substr($parts[1], 0, 1));
        }

        return $letters;
    }
}
