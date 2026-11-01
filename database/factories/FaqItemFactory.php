<?php

namespace Database\Factories;

use App\Models\FaqCategory;
use App\Models\FaqItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FaqItem>
 */
class FaqItemFactory extends Factory
{
    protected $model = FaqItem::class;

    public function definition(): array
    {
        return [
            'faq_category_id' => FaqCategory::factory(),
            'question' => rtrim(fake()->sentence(), '.').'?',
            'answer' => fake()->paragraph(),
            'sort_order' => fake()->numberBetween(0, 20),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
