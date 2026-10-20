<?php

namespace App\Services\Payments;

/**
 * A normalised reply from a payment gateway API call.
 */
final class GatewayResponse
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public readonly bool $ok,
        public readonly string $message,
        public readonly array $data = [],
    ) {}

    public static function failed(string $message): self
    {
        return new self(false, $message);
    }
}
