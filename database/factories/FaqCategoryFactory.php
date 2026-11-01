<?php

namespace Database\Factories;

use App\Models\FaqCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<FaqCategory>
 */
class FaqCategoryFactory extends Factory
{
    protected $model = FaqCategory::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'icon' => fake()->randomElement(['fa-circle-question', 'fa-wallet', 'fa-chart-pie', 'fa-shield-halved', 'fa-credit-card']),
            'sort_order' => fake()->numberBetween(0, 20),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
