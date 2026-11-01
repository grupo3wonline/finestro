<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Stripe no longer knows the subscription or customer (resource_missing /
 * HTTP 404), e.g. it was already canceled or deleted on the Stripe side.
 */
class StripeResourceMissingException extends \RuntimeException {}
