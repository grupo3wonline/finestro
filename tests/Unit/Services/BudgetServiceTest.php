<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Interfaces\Services\BudgetServiceInterface;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Testes unitários do BudgetService: alertas de orçamento estourado
 * e cópia de orçamentos para o mês seguinte.
 */
class BudgetServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Wallet $wallet;

    private BudgetServiceInterface $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->wallet = Wallet::factory()->for($this->user)->create([
            'type' => 'checking',
            'balance' => 5000,
        ]);

        $this->actingAs($this->user);

        $this->service = app(BudgetServiceInterface::class);
    }

    public function test_get_alerts_returns_over_threshold_budgets(): void
    {
        $year = now()->year;
        $month = now()->month;

        $category = Category::factory()->for($this->user)->create(['type' => 'expense']);

        // Orçamento de R$ 100,00 para a categoria.
        Budget::factory()->for($this->user)->create([
            'category_id' => $category->id,
            'year' => $year,
            'month' => $month,
            'amount' => '100.00',
            'alert_threshold' => 80,
        ]);

        // Despesa de R$ 85,00 (85% do orçamento — acima do alerta de 80%).
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'wallet_id' => $this->wallet->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'status' => 'paid',
            'amount' => '85.00',
            'date' => now()->toDateString(),
            'is_recurring' => false,
        ]);

        $alerts = $this->service->getAlerts($year, $month);

        $this->assertGreaterThanOrEqual(1, $alerts->count());
    }

    public function test_copy_previous_copies_all_budgets(): void
    {
        $prevMonth = now()->subMonthNoOverflow();
        $targetYear = now()->year;
        $targetMonth = now()->month;

        // Usar categorias distintas para que cada orçamento tenha category_id
        // único — dois orçamentos gerais (category_id=null) resultariam apenas
        // em 1 cópia, pois o segundo seria tratado como duplicado.
        $categories = Category::factory()->for($this->user)->count(2)->create(['type' => 'expense']);

        foreach ($categories as $category) {
            Budget::factory()->for($this->user)->create([
                'category_id' => $category->id,
                'year' => $prevMonth->year,
                'month' => $prevMonth->month,
                'amount' => '200.00',
            ]);
        }

        $this->service->copyFromPreviousMonth($targetYear, $targetMonth);

        $copied = Budget::where('user_id', $this->user->id)
            ->where('year', $targetYear)
            ->where('month', $targetMonth)
            ->count();

        $this->assertSame(2, $copied);
    }
}
