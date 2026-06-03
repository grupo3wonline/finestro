<?php

namespace Database\Factories;

use App\Models\Goal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Goal>
 */
class GoalFactory extends Factory
{
    protected $model = Goal::class;

    public function definition(): array
    {
        $targetAmount = fake()->randomFloat(2, 1000, 100000);
        $currentAmount = fake()->randomFloat(2, 0, $targetAmount * 0.8);

        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement(['Reserva de Emergência', 'Viagem', 'Carro Novo', 'Casa Própria', 'Aposentadoria', 'Curso', 'Equipamento']),
            'description' => fake()->optional()->sentence(),
            'target_amount' => $targetAmount,
            'current_amount' => $currentAmount,
            'deadline' => fake()->dateTimeBetween('+1 month', '+2 years'),
            'status' => 'active',
            'icon' => fake()->randomElement(['fa-bullseye', 'fa-piggy-bank', 'fa-plane', 'fa-car', 'fa-home', 'fa-graduation-cap']),
            'color' => fake()->randomElement(['#10B981', '#3B82F6', '#F59E0B', '#8B5CF6', '#EF4444']),
        ];
    }
}
