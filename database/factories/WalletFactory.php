<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Wallet>
 */
class WalletFactory extends Factory
{
    protected $model = Wallet::class;

    public function definition(): array
    {
        $types = ['checking', 'savings', 'credit', 'investment', 'cash'];
        $colors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#06B6D4'];
        $icons = ['fa-wallet', 'fa-university', 'fa-credit-card', 'fa-piggy-bank', 'fa-chart-line', 'fa-money-bill'];

        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement(['Conta Corrente', 'Poupança', 'Cartão Nubank', 'Investimentos', 'Dinheiro', 'Conta Salário']),
            'type' => fake()->randomElement($types),
            'balance' => fake()->randomFloat(2, 100, 50000),
            'currency' => 'BRL',
            'color' => fake()->randomElement($colors),
            'icon' => fake()->randomElement($icons),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
