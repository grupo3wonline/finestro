<?php

namespace Database\Factories;

use App\Models\PaymentHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentHistory>
 */
class PaymentHistoryFactory extends Factory
{
    protected $model = PaymentHistory::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'subscription_id' => null,
            'stripe_invoice_id' => 'in_'.fake()->bothify('##??##??##'),
            'stripe_payment_intent_id' => 'pi_'.fake()->bothify('##??##??##'),
            'amount' => fake()->randomFloat(2, 19, 99),
            'currency' => 'BRL',
            'status' => 'paid',
            'description' => 'Assinatura Finestro Profissional',
            'paid_at' => now(),
        ];
    }

    /**
     * Failed payment attempt.
     */
    public function failed(): static
    {
        return $this->state(fn (): array => [
            'status' => 'failed',
            'paid_at' => null,
        ]);
    }

    /**
     * Pending payment.
     */
    public function pending(): static
    {
        return $this->state(fn (): array => [
            'status' => 'pending',
            'paid_at' => null,
        ]);
    }
}
