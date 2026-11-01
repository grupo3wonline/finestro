<?php

namespace App\Repositories;

use App\DTOs\SubscriptionDTO;
use App\Interfaces\Repositories\SubscriptionRepositoryInterface;
use App\Models\Subscription;

class SubscriptionRepository implements SubscriptionRepositoryInterface
{
    public function __construct(
        protected Subscription $model
    ) {}

    public function findLatestForUser(string $userId): ?Subscription
    {
        return $this->model->newQuery()
            ->where('user_id', $userId)
            ->latest()
            ->first();
    }

    public function findByStripeId(string $stripeSubscriptionId): ?Subscription
    {
        return $this->model->newQuery()
            ->where('stripe_subscription_id', $stripeSubscriptionId)
            ->first();
    }

    public function updateOrCreateForUser(string $userId, SubscriptionDTO $dto): Subscription
    {
        return $this->model->newQuery()->updateOrCreate(
            ['user_id' => $userId],
            $dto->toArray(),
        );
    }

    public function updateAttributes(Subscription $subscription, array $attributes): Subscription
    {
        $subscription->update($attributes);

        return $subscription->refresh();
    }
}
