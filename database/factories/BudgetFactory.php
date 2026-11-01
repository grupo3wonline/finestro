<?php

namespace Database\Factories;

use App\Models\Budget;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Budget>
 */
class BudgetFactory extends Factory
{
    protected $model = Budget::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => null,
            'year' => (int) now()->year,
            'month' => (int) now()->month,
            'amount' => fake()->randomFloat(2, 200, 5000),
            'alert_threshold' => fake()->randomElement([70, 80, 90]),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Budget scoped to a specific category (not a general budget).
     */
    public function forCategory(string $categoryId): static
    {
        return $this->state(fn (): array => ['category_id' => $categoryId]);
    }

    /**
     * Budget for a given year/month.
     */
    public function forPeriod(int $year, int $month): static
    {
        return $this->state(fn (): array => ['year' => $year, 'month' => $month]);
    }
}
