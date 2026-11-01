<?php

declare(strict_types=1);

namespace App\Interfaces\Repositories;

use App\DTOs\SubscriptionDTO;
use App\Models\Subscription;

interface SubscriptionRepositoryInterface
{
    /**
     * Get the latest subscription for a given user.
     */
    public function findLatestForUser(string $userId): ?Subscription;

    /**
     * Find a subscription by its Stripe subscription id.
     */
    public function findByStripeId(string $stripeSubscriptionId): ?Subscription;

    /**
     * Create or update the user's subscription from a DTO.
     */
    public function updateOrCreateForUser(string $userId, SubscriptionDTO $dto): Subscription;

    /**
     * Persist arbitrary attribute changes on a subscription.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function updateAttributes(Subscription $subscription, array $attributes): Subscription;
}
