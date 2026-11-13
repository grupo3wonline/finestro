<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CRUD de categorias e orçamentos, incluindo limite do plano gratuito
 * e cópia de orçamentos do mês anterior.
 */
class CategoryBudgetCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    // -------------------------------------------------------------------------
    // Categorias
    // -------------------------------------------------------------------------

    public function test_user_can_create_category(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('categories.store'), [
                'name' => 'Alimentação',
                'type' => 'expense',
                'icon' => 'fa-utensils',
                'color' => '#10B981',
            ]);

        $response->assertRedirect(route('categories.index'));
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('categories', [
            'user_id' => $this->user->id,
            'name' => 'Alimentação',
        ]);
    }

    public function test_free_plan_cannot_exceed_category_limit(): void
    {
        // Plano gratuito: máximo de 5 categorias.
        Category::factory()->for($this->user)->count(5)->create(['type' => 'expense']);

        $response = $this->actingAs($this->user)
            ->post(route('categories.store'), [
                'name' => 'Sexta Categoria',
                'type' => 'expense',
            ]);

        $response->assertRedirect(route('categories.index'));
        $response->assertSessionHas('error');
        $this->assertSame(5, Category::where('user_id', $this->user->id)->count());
    }

    public function test_import_suggested_creates_default_categories(): void
    {
        $this->assertSame(0, Category::where('user_id', $this->user->id)->count());

        $response = $this->actingAs($this->user)
            ->post(route('categories.import-suggested'));

        $response->assertRedirect(route('categories.index'));

        $this->assertGreaterThanOrEqual(1, Category::where('user_id', $this->user->id)->count());
    }

    // -------------------------------------------------------------------------
    // Orçamentos
    // -------------------------------------------------------------------------

    public function test_user_can_store_budget(): void
    {
        $category = Category::factory()->for($this->user)->create(['type' => 'expense']);

        $response = $this->actingAs($this->user)
            ->post(route('budgets.store'), [
                'category_id' => $category->id,
                'amount' => '800',
                'year' => now()->year,
                'month' => now()->month,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('budgets', [
            'user_id' => $this->user->id,
            'category_id' => $category->id,
        ]);
    }

    public function test_free_plan_cannot_exceed_budget_limit(): void
    {
        $year = now()->year;
        $month = now()->month;

        // Plano gratuito: máximo de 3 orçamentos por mês.
        Budget::factory()->for($this->user)->count(3)->create([
            'year' => $year,
            'month' => $month,
        ]);

        // Criar uma categoria diferente para o 4.º orçamento.
        $category = Category::factory()->for($this->user)->create(['type' => 'expense']);

        $response = $this->actingAs($this->user)
            ->post(route('budgets.store'), [
                'category_id' => $category->id,
                'amount' => '500',
                'year' => $year,
                'month' => $month,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertSame(3, Budget::where('user_id', $this->user->id)
            ->where('year', $year)
            ->where('month', $month)
            ->count());
    }

    public function test_copy_previous_month_duplicates_budgets(): void
    {
        $prevMonth = now()->subMonthNoOverflow();
        $targetYear = now()->year;
        $targetMonth = now()->month;

        // Criar 3 categorias distintas de despesa para garantir que cada
        // orçamento do mês anterior tenha category_id único — assim a cópia
        // não descarta os duplicados (que teriam category_id = null e contariam
        // como o mesmo orçamento geral).
        $categories = Category::factory()->for($this->user)->count(3)->create(['type' => 'expense']);

        foreach ($categories as $category) {
            Budget::factory()->for($this->user)->create([
                'category_id' => $category->id,
                'year' => $prevMonth->year,
                'month' => $prevMonth->month,
                'amount' => '300.00',
            ]);
        }

        $response = $this->actingAs($this->user)
            ->post(route('budgets.copy-previous'), [
                'year' => $targetYear,
                'month' => $targetMonth,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertSame(3, Budget::where('user_id', $this->user->id)
            ->where('year', $targetYear)
            ->where('month', $targetMonth)
            ->count());
    }
}
