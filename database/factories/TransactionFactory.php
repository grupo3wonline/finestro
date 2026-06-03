<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        $type = fake()->randomElement(['income', 'expense']);
        $descriptions = $type === 'income'
            ? ['Salário', 'Freelance', 'Rendimento', 'Venda', 'Reembolso']
            : ['Supermercado', 'Combustível', 'Aluguel', 'Conta de Luz', 'Internet', 'Restaurante', 'Farmácia'];

        return [
            'user_id' => User::factory(),
            'wallet_id' => Wallet::factory(),
            'category_id' => null,
            'type' => $type,
            'amount' => fake()->randomFloat(2, 10, 5000),
            'description' => fake()->randomElement($descriptions),
            'date' => fake()->dateTimeBetween('-3 months', 'now'),
            'is_recurring' => fake()->boolean(20),
            'recurring_frequency' => null,
            'receipt_path' => null,
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }
}
