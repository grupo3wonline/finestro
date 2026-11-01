<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * The Checkout session does not exist, is not complete, or does not belong to
 * the authenticated user. Rendered as 404 so session ids cannot be probed.
 */
class CheckoutSessionNotFoundException extends \RuntimeException
{
    public function __construct(string $message = 'Checkout session not found.')
    {
        parent::__construct($message);
    }
}
