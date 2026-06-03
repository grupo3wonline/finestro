<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $incomeCategories = ['Salário', 'Freelance', 'Investimentos', 'Vendas', 'Outros'];
        $expenseCategories = ['Alimentação', 'Transporte', 'Moradia', 'Saúde', 'Educação', 'Lazer', 'Compras'];
        $type = fake()->randomElement(['income', 'expense']);

        return [
            'user_id' => User::factory(),
            'name' => $type === 'income' ? fake()->randomElement($incomeCategories) : fake()->randomElement($expenseCategories),
            'type' => $type,
            'icon' => 'fa-tag',
            'color' => fake()->hexColor(),
        ];
    }
}
