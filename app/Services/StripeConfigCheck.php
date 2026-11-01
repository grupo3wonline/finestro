<?php

declare(strict_types=1);

namespace App\Services;

use App\Interfaces\Services\StripeConfigCheckInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class StripeConfigCheck implements StripeConfigCheckInterface
{
    private const string WARNING_CACHE_KEY = 'finestro:stripe:test-key-warning';

    public function isTestKeyInProduction(): bool
    {
        return app()->isProduction()
            && str_starts_with((string) config('stripe.secret_key'), 'sk_test_');
    }

    public function reportIfMisconfigured(): void
    {
        if (! $this->isTestKeyInProduction()) {
            return;
        }

        // Cache::add only succeeds when the key is absent: one log per day.
        if (Cache::add(self::WARNING_CACHE_KEY, true, now()->addDay())) {
            Log::critical('Stripe is configured with a test secret key in production; real payments will not be processed.', [
                'key_mode' => 'test',
            ]);
        }
    }
}
