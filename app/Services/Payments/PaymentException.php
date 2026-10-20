<?php

namespace App\Services\Payments;

use RuntimeException;

/**
 * A payment that cannot be started. The message is safe to show the customer.
 */
class PaymentException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status = 422)
    {
        parent::__construct($message);
    }
}
