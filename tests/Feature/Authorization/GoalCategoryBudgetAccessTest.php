<?php

namespace Tests\Feature\Authorization;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Goal;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Goals, categories and budgets: foreign records answer 404 (never 500 or a
 * fake "success") and never appear in another user's listings.
 */
class GoalCategoryBudgetAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $intruder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->intruder = User::factory()->create();
    }

    public function test_goal_routes_return_404_for_another_user(): void
    {
        $goal = Goal::factory()->for($this->owner)->create(['name' => 'Meta Secreta do Dono', 'target_amount' => 1000]);

        $this->actingAs($this->intruder);

        $this->get(route('goals.show', $goal))->assertNotFound();
        $this->get(route('goals.edit', $goal))->assertNotFound();
        $this->put(route('goals.update', $goal), ['name' => 'Invadida', 'target_amount' => 5])->assertNotFound();
        $this->delete(route('goals.destroy', $goal))->assertNotFound();
        $this->get(route('goals.index'))->assertOk()->assertDontSee('Meta Secreta do Dono');

        $this->assertDatabaseHas('goals', ['id' => $goal->id, 'name' => 'Meta Secreta do Dono', 'deleted_at' => null]);
    }

    public function test_category_routes_return_404_for_another_user(): void
    {
        $category = Category::factory()->for($this->owner)->create(['name' => 'Categoria Secreta do Dono', 'type' => 'expense']);

        $this->actingAs($this->intruder);

        $this->get(route('categories.edit', $category))->assertNotFound();
        $this->put(route('categories.update', $category), ['name' => 'Invadida', 'type' => 'expense'])->assertNotFound();
        $this->delete(route('categories.destroy', $category))->assertNotFound();
        $this->get(route('categories.index'))->assertOk()->assertDontSee('Categoria Secreta do Dono');

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Categoria Secreta do Dono', 'deleted_at' => null]);
    }

    public function test_budget_routes_return_404_for_another_user(): void
    {
        $category = Category::factory()->for($this->owner)->create(['name' => 'Categoria Orcada do Dono', 'type' => 'expense']);
        $budget = Budget::factory()->for($this->owner)->forCategory($category->id)->create(['amount' => 500]);

        $this->actingAs($this->intruder);

        $this->put(route('budgets.update', $budget), ['amount' => 1])->assertNotFound();
        $this->delete(route('budgets.destroy', $budget))->assertNotFound();
        $this->get(route('budgets.index'))->assertOk()->assertDontSee('Categoria Orcada do Dono');

        $this->assertDatabaseHas('budgets', ['id' => $budget->id, 'deleted_at' => null]);
        $this->assertEquals('500.00', $budget->fresh()->amount);
    }

    public function test_foreign_category_cannot_be_used_in_own_records(): void
    {
        $foreignCategory = Category::factory()->for($this->owner)->create(['type' => 'expense']);
        $intruderWallet = Wallet::factory()->for($this->intruder)->create(['type' => 'checking', 'balance' => 100]);

        $this->actingAs($this->intruder);

        $this->post(route('transactions.store'), [
            'wallet_id' => $intruderWallet->id,
            'category_id' => $foreignCategory->id,
            'type' => 'expense',
            'amount' => '10.00',
            'description' => 'Teste',
            'date' => now()->toDateString(),
        ])->assertSessionHasErrors('category_id');

        $this->post(route('budgets.store'), [
            'category_id' => $foreignCategory->id,
            'year' => now()->year,
            'month' => now()->month,
            'amount' => '100',
        ])->assertSessionHasErrors('category_id');

        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('budgets', 0);
    }
}
