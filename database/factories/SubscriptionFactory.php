<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        $start = now()->subDays(rand(1, 25));

        return [
            'user_id' => User::factory(),
            'stripe_subscription_id' => 'sub_' . fake()->regexify('[A-Za-z0-9]{24}'),
            'stripe_customer_id' => 'cus_' . fake()->regexify('[A-Za-z0-9]{14}'),
            'stripe_price_id' => 'price_' . fake()->regexify('[A-Za-z0-9]{24}'),
            'plan' => 'professional',
            'status' => 'active',
            'current_period_start' => $start,
            'current_period_end' => $start->copy()->addMonth(),
        ];
    }

    public function free(): static
    {
        return $this->state(fn(array $attributes) => [
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
        return $this->state(fn(array $attributes) => [
            'status' => 'canceled',
            'canceled_at' => now(),
        ]);
    }
}
