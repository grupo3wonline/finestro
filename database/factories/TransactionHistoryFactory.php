<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\TransactionHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransactionHistory>
 */
class TransactionHistoryFactory extends Factory
{
    protected $model = TransactionHistory::class;

    public function definition(): array
    {
        return [
            'transaction_id' => Transaction::factory(),
            'user_id' => User::factory(),
            'action' => fake()->randomElement([
                TransactionHistory::ACTION_CREATED,
                TransactionHistory::ACTION_UPDATED,
                TransactionHistory::ACTION_PAID,
                TransactionHistory::ACTION_UNPAID,
            ]),
            'changes' => null,
            'reason' => fake()->optional()->sentence(),
        ];
    }

    /**
     * History entry for a given action.
     */
    public function action(string $action): static
    {
        return $this->state(fn (): array => ['action' => $action]);
    }
}
