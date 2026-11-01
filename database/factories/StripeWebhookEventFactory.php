<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\StripeWebhookEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StripeWebhookEvent>
 */
class StripeWebhookEventFactory extends Factory
{
    protected $model = StripeWebhookEvent::class;

    public function definition(): array
    {
        return [
            'stripe_event_id' => 'evt_'.fake()->unique()->regexify('[A-Za-z0-9]{24}'),
            'type' => 'invoice.paid',
            'processed_at' => now(),
        ];
    }
}
