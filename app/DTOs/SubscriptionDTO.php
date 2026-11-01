<?php

namespace App\DTOs;

use App\Enums\SubscriptionStatus;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Data;

class SubscriptionDTO extends Data
{
    public function __construct(
        public readonly ?string $stripeSubscriptionId = null,
        public readonly ?string $stripeCustomerId = null,
        public readonly ?string $stripePriceId = null,
        public readonly ?string $plan = null,
        public readonly ?SubscriptionStatus $status = null,
        public readonly ?CarbonInterface $currentPeriodStart = null,
        public readonly ?CarbonInterface $currentPeriodEnd = null,
        public readonly ?CarbonInterface $canceledAt = null,
        public readonly ?bool $cancelAtPeriodEnd = null,
    ) {}

    /**
     * Create DTO from an array of attributes.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            stripeSubscriptionId: $data['stripe_subscription_id'] ?? null,
            stripeCustomerId: $data['stripe_customer_id'] ?? null,
            stripePriceId: $data['stripe_price_id'] ?? null,
            plan: $data['plan'] ?? null,
            status: self::normalizeStatus($data['status'] ?? null),
            currentPeriodStart: $data['current_period_start'] ?? null,
            currentPeriodEnd: $data['current_period_end'] ?? null,
            canceledAt: $data['canceled_at'] ?? null,
            cancelAtPeriodEnd: isset($data['cancel_at_period_end']) ? (bool) $data['cancel_at_period_end'] : null,
        );
    }

    /**
     * Accept either an enum instance or its string value (e.g. a raw Stripe
     * status) and return the enum, or null when absent/unknown.
     */
    private static function normalizeStatus(mixed $status): ?SubscriptionStatus
    {
        if ($status instanceof SubscriptionStatus) {
            return $status;
        }

        return is_string($status) ? SubscriptionStatus::tryFrom($status) : null;
    }

    /**
     * Convert DTO to array for model persistence (nulls stripped, false kept).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'stripe_subscription_id' => $this->stripeSubscriptionId,
            'stripe_customer_id' => $this->stripeCustomerId,
            'stripe_price_id' => $this->stripePriceId,
            'plan' => $this->plan,
            'status' => $this->status?->value,
            'current_period_start' => $this->currentPeriodStart,
            'current_period_end' => $this->currentPeriodEnd,
            'canceled_at' => $this->canceledAt,
            'cancel_at_period_end' => $this->cancelAtPeriodEnd,
        ], fn (string|CarbonInterface|bool|null $value): bool => ! is_null($value));
    }
}
