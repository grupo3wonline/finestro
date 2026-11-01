<?php

namespace App\Repositories;

use App\Interfaces\Repositories\StripeWebhookEventRepositoryInterface;
use App\Models\StripeWebhookEvent;
use Illuminate\Database\UniqueConstraintViolationException;

class StripeWebhookEventRepository implements StripeWebhookEventRepositoryInterface
{
    public function __construct(
        protected StripeWebhookEvent $model
    ) {}

    public function markProcessed(string $eventId, string $type): bool
    {
        if ($this->model->newQuery()->withTrashed()->where('stripe_event_id', $eventId)->exists()) {
            return false;
        }

        try {
            $this->model->newQuery()->create([
                'stripe_event_id' => $eventId,
                'type' => $type,
                'processed_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            return false;
        }

        return true;
    }
}
