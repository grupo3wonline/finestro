<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Interfaces\Services\TransactionServiceInterface;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Testes unitários do TransactionService: resumo de receitas/despesas
 * e distribuição correta de centavos em parcelamentos.
 */
class TransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Wallet $wallet;

    private TransactionServiceInterface $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->wallet = Wallet::factory()->for($this->user)->create([
            'type' => 'checking',
            'balance' => 10000,
        ]);

        // Autentica o usuário para que o serviço use o escopo correto.
        $this->actingAs($this->user);

        $this->service = app(TransactionServiceInterface::class);
    }

    public function test_summary_returns_income_and_expense_totals(): void
    {
        $month = now()->month;
        $year = now()->year;

        // Receita paga: R$ 500,00
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'wallet_id' => $this->wallet->id,
            'type' => 'income',
            'status' => 'paid',
            'amount' => '500.00',
            'date' => now()->toDateString(),
            'is_recurring' => false,
        ]);

        // Despesa paga: R$ 200,00
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'wallet_id' => $this->wallet->id,
            'type' => 'expense',
            'status' => 'paid',
            'amount' => '200.00',
            'date' => now()->toDateString(),
            'is_recurring' => false,
        ]);

        // Despesa pendente: R$ 100,00 (não deve entrar nos totais pagos)
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'wallet_id' => $this->wallet->id,
            'type' => 'expense',
            'status' => 'pending',
            'amount' => '100.00',
            'date' => now()->toDateString(),
            'is_recurring' => false,
        ]);

        $summary = $this->service->getSummary([
            'month' => $month,
            'year' => $year,
        ]);

        $this->assertEqualsWithDelta(500.00, (float) ($summary['income_paid'] ?? $summary['income'] ?? 0), 0.01);
        $this->assertEqualsWithDelta(200.00, (float) ($summary['expense_paid'] ?? $summary['expenses'] ?? 0), 0.01);
    }

    public function test_installment_penny_distribution(): void
    {
        // R$ 100,00 ÷ 3 parcelas = [33,33 | 33,33 | 33,34]
        $this->actingAs($this->user)
            ->post(route('transactions.store'), [
                'wallet_id' => $this->wallet->id,
                'type' => 'expense',
                'amount' => '100.00',
                'description' => 'Parcelamento centavos',
                'date' => now()->toDateString(),
                'installments' => 3,
                'status' => 'pending',
            ])
            ->assertSessionHasNoErrors();

        $installments = Transaction::where('user_id', $this->user->id)
            ->orderBy('installment_number')
            ->get();

        $this->assertCount(3, $installments);
        $this->assertEquals(['33.33', '33.33', '33.34'], $installments->pluck('amount')->all());
        $this->assertEqualsWithDelta(100.00, (float) $installments->sum('amount'), 0.001);
    }
}
