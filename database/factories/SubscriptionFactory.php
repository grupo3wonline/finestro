<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        $start = now()->subDays(random_int(1, 25));

        return [
            'user_id' => User::factory(),
            'stripe_subscription_id' => 'sub_'.fake()->regexify('[A-Za-z0-9]{24}'),
            'stripe_customer_id' => 'cus_'.fake()->regexify('[A-Za-z0-9]{14}'),
            'stripe_price_id' => 'price_'.fake()->regexify('[A-Za-z0-9]{24}'),
            'plan' => 'professional',
            'status' => 'active',
            'current_period_start' => $start,
            'current_period_end' => $start->copy()->addMonth(),
        ];
    }

    public function free(): static
    {
        return $this->state(fn (array $attributes): array => [
            'plan' => 'free',
            'stripe_subscription_id' => null,
            'stripe_customer_id' => null,
            'stripe_price_id' => null,
            'current_period_start' => null,
            'current_period_end' => null,
        ]);
    }

    public function canceled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'canceled',
            'canceled_at' => now(),
        ]);
    }

    /**
     * Cancellation scheduled for period end; access kept until then.
     */
    public function pendingCancellation(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'active',
            'cancel_at_period_end' => true,
            'canceled_at' => now(),
        ]);
    }

    /**
     * Period already ended.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'current_period_start' => now()->subMonth()->subDay(),
            'current_period_end' => now()->subDay(),
        ]);
    }

    /**
     * Renewal payment failed.
     */
    public function pastDue(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'past_due',
        ]);
    }
}
