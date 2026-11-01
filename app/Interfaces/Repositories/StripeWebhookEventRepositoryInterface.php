<?php

declare(strict_types=1);

namespace App\Interfaces\Repositories;

interface StripeWebhookEventRepositoryInterface
{
    /**
     * Record the event as processed. Returns false when it was already recorded.
     */
    public function markProcessed(string $eventId, string $type): bool;
}
