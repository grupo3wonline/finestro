<?php

declare(strict_types=1);

namespace App\Interfaces\Services;

/**
 * Detects a Stripe test secret key configured in production.
 */
interface StripeConfigCheckInterface
{
    /**
     * True when the app runs in production with an `sk_test_` secret key.
     */
    public function isTestKeyInProduction(): bool;

    /**
     * Log a critical message (at most once per day) when misconfigured.
     * Never logs the key itself.
     */
    public function reportIfMisconfigured(): void;
}
