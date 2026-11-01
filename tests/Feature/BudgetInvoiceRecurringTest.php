<?php

namespace Tests\Feature;

use App\Interfaces\Services\BudgetServiceInterface;
use App\Mail\FinancialReportMail;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Database\Factories\SubscriptionFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BudgetInvoiceRecurringTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Wallet $checking;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->checking = Wallet::factory()->for($this->user)->create(['type' => 'checking', 'balance' => 5000]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function professional(User $user): User
    {
        SubscriptionFactory::new()->for($user)->create(['current_period_end' => now()->addMonth()]);

        return $user->fresh();
    }

    private function expense(array $attributes): Transaction
    {
        return Transaction::factory()->create(array_merge([
            'user_id' => $this->user->id,
            'wallet_id' => $this->checking->id,
            'type' => 'expense',
            'status' => 'paid',
            'is_recurring' => false,
            'date' => now()->toDateString(),
        ], $attributes));
    }

    /*
    |--------------------------------------------------------------------------
    | Orçamentos
    |--------------------------------------------------------------------------
    */

    public function test_budget_usage_counts_paid_and_pending_expenses_of_the_month(): void
    {
        $food = Category::factory()->for($this->user)->create(['type' => 'expense', 'name' => 'Alimentação']);

        $this->expense(['category_id' => $food->id, 'amount' => 600]);
        $this->expense(['category_id' => $food->id, 'amount' => 250, 'status' => 'pending']);
        $this->expense(['category_id' => $food->id, 'amount' => 999, 'date' => now()->subMonthNoOverflow()->toDateString()]); // outro mês

        $this->actingAs($this->user)->post(route('budgets.store'), [
            'category_id' => $food->id, 'year' => now()->year, 'month' => now()->month, 'amount' => '1000', 'alert_threshold' => 80,
        ])->assertSessionHasNoErrors();

        $response = $this->actingAs($this->user)->get(route('budgets.index'));
        $response->assertOk()->assertSee('Alimentação')->assertSee('85%');

        $budget = app(BudgetServiceInterface::class)
            ->getBudgetsWithUsage(now()->year, now()->month)->first();
        $this->assertEquals(850.0, $budget->spent);
        $this->assertEquals('warning', $budget->health);
    }

    public function test_free_plan_is_limited_to_three_budgets_per_month(): void
    {
        $categories = Category::factory()->for($this->user)->count(4)->create(['type' => 'expense']);

        foreach ($categories->take(3) as $category) {
            $this->actingAs($this->user)->post(route('budgets.store'), [
                'category_id' => $category->id, 'year' => now()->year, 'month' => now()->month, 'amount' => 100,
            ])->assertSessionHasNoErrors();
        }

        $this->actingAs($this->user)->post(route('budgets.store'), [
            'category_id' => $categories[3]->id, 'year' => now()->year, 'month' => now()->month, 'amount' => 100,
        ])->assertSessionHas('error');

        $this->assertEquals(3, Budget::count());
    }

    public function test_repeat_until_december_and_copy_previous_month(): void
    {
        $user = $this->professional($this->user);

        $this->actingAs($user)->post(route('budgets.store'), [
            'year' => 2026, 'month' => 10, 'amount' => '3000', 'repeat_until_december' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertEquals([10, 11, 12], Budget::orderBy('month')->pluck('month')->all());

        $this->actingAs($user)->post(route('budgets.copy-previous', ['year' => 2027, 'month' => 1]))->assertSessionHas('success');
        $this->assertDatabaseHas('budgets', ['year' => 2027, 'month' => 1, 'amount' => 3000]);
    }

    public function test_budget_category_must_belong_to_user(): void
    {
        $foreign = Category::factory()->create(['type' => 'expense']);

        $this->actingAs($this->user)->post(route('budgets.store'), [
            'category_id' => $foreign->id, 'year' => now()->year, 'month' => now()->month, 'amount' => 100,
        ])->assertSessionHasErrors('category_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Fatura do cartão
    |--------------------------------------------------------------------------
    */

    public function test_invoice_cycle_boundaries(): void
    {
        $card = new Wallet(['type' => 'credit', 'closing_day' => 5, 'due_day' => 12]);

        // Compra antes do fechamento cai na fatura do mesmo mês.
        $cycle = $card->invoiceCycleFor(Carbon::parse('2026-03-03'));
        $this->assertEquals('2026-02-06', $cycle['start']->toDateString());
        $this->assertEquals('2026-03-05', $cycle['end']->toDateString());
        $this->assertEquals('2026-03-12', $cycle['due']->toDateString());

        // Compra depois do fechamento vai para a fatura seguinte.
        $cycle = $card->invoiceCycleFor(Carbon::parse('2026-03-06'));
        $this->assertEquals('2026-04-05', $cycle['end']->toDateString());

        // Vencimento antes do dia de fechamento → vence no mês seguinte.
        $card2 = new Wallet(['type' => 'credit', 'closing_day' => 25, 'due_day' => 3]);
        $cycle = $card2->invoiceCycleFor(Carbon::parse('2026-01-20'));
        $this->assertEquals('2026-01-25', $cycle['end']->toDateString());
        $this->assertEquals('2026-02-03', $cycle['due']->toDateString());

        // Dia 31 em fevereiro é ajustado para o último dia do mês.
        $card3 = new Wallet(['type' => 'credit', 'closing_day' => 31, 'due_day' => 10]);
        $this->assertEquals('2026-02-28', $card3->invoiceCycleFor(Carbon::parse('2026-02-10'))['end']->toDateString());
    }

    public function test_invoice_page_and_payment_between_wallets(): void
    {
        Carbon::setTestNow('2026-03-10');

        $card = Wallet::factory()->for($this->user)->create([
            'type' => 'credit', 'balance' => 0, 'credit_limit' => 3000, 'closing_day' => 5, 'due_day' => 12,
        ]);

        // Compra no ciclo 06/02–05/03 (fatura de março, já fechada).
        $this->actingAs($this->user)->post(route('transactions.store'), [
            'wallet_id' => $card->id, 'type' => 'expense', 'amount' => '450', 'description' => 'Mercado', 'date' => '2026-03-01',
        ]);

        $this->actingAs($this->user)->get(route('wallets.invoice', ['wallet' => $card->id, 'ref' => '2026-03']))
            ->assertOk()->assertSee('Mercado')->assertSee('450,00');

        $this->actingAs($this->user)->post(route('wallets.invoice.pay', $card), [
            'source_wallet_id' => $this->checking->id, 'amount' => '450', 'date' => '2026-03-10', 'ref' => '2026-03',
        ])->assertSessionHasNoErrors();

        $this->assertEquals(0.00, (float) $card->fresh()->balance);
        $this->assertEquals(4550.00, (float) $this->checking->fresh()->balance);
        $this->assertEquals(2, Transaction::where('origin', Transaction::ORIGIN_INVOICE_PAYMENT)->count());
    }

    public function test_cannot_pay_invoice_from_another_users_wallet(): void
    {
        $card = Wallet::factory()->for($this->user)->create(['type' => 'credit', 'closing_day' => 5, 'due_day' => 12]);
        $foreign = Wallet::factory()->create(['type' => 'checking']);

        $this->actingAs($this->user)->post(route('wallets.invoice.pay', $card), [
            'source_wallet_id' => $foreign->id, 'amount' => '10', 'date' => now()->toDateString(), 'ref' => now()->format('Y-m'),
        ])->assertSessionHasErrors('source_wallet_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Recorrência
    |--------------------------------------------------------------------------
    */

    public function test_recurring_generates_pending_ahead_catches_up_and_keeps_anchor_day(): void
    {
        Carbon::setTestNow('2026-05-25');

        $source = $this->expense([
            'description' => 'Aluguel', 'amount' => 1500, 'date' => '2026-01-31',
            'is_recurring' => true, 'recurring_frequency' => 'monthly', 'payment_method' => 'boleto',
        ]);

        $this->artisan('transactions:process-recurring')->assertSuccessful();

        $dates = Transaction::where('recurring_source_id', $source->id)->orderBy('date')->get()
            ->map(fn ($t) => $t->date->toDateString())->all();

        // Recupera fev→mai e ainda cria 31/05 (dentro dos 7 dias de antecedência).
        $this->assertEquals(['2026-02-28', '2026-03-31', '2026-04-30', '2026-05-31'], $dates);
        $this->assertEquals(4, Transaction::where('recurring_source_id', $source->id)->where('status', 'pending')->count());

        // Rodar de novo não duplica.
        $this->artisan('transactions:process-recurring')->assertSuccessful();
        $this->assertEquals(4, Transaction::where('recurring_source_id', $source->id)->count());
    }

    public function test_recurring_series_stops_when_last_occurrence_is_unchecked_and_auto_debit_is_paid(): void
    {
        Carbon::setTestNow('2026-03-15');

        $netflix = $this->expense([
            'description' => 'Streaming', 'amount' => 40, 'date' => '2026-02-10',
            'is_recurring' => true, 'recurring_frequency' => 'monthly', 'payment_method' => 'auto_debit',
        ]);

        $this->artisan('transactions:process-recurring');
        $generated = Transaction::where('recurring_source_id', $netflix->id)->firstOrFail();
        $this->assertEquals('2026-03-10', $generated->date->toDateString());
        $this->assertTrue($generated->isPaid(), 'Débito automático entra como pago');

        // Usuário encerra a série desmarcando a última ocorrência.
        $generated->update(['is_recurring' => false]);
        Carbon::setTestNow('2026-06-15');
        $this->artisan('transactions:process-recurring');

        $this->assertEquals(1, Transaction::where('recurring_source_id', $netflix->id)->count());
    }

    /*
    |--------------------------------------------------------------------------
    | Categorias sugeridas
    |--------------------------------------------------------------------------
    */

    public function test_import_suggested_categories_respects_free_plan_limit(): void
    {
        $this->actingAs($this->user)->post(route('categories.import-suggested'))->assertRedirect();
        $this->assertEquals(5, Category::where('user_id', $this->user->id)->count());

        $pro = $this->professional(User::factory()->create());
        $this->actingAs($pro)->post(route('categories.import-suggested'));
        $this->assertEquals(22, Category::where('user_id', $pro->id)->count());

        // Idempotente: não duplica.
        $this->actingAs($pro)->post(route('categories.import-suggested'));
        $this->assertEquals(22, Category::where('user_id', $pro->id)->count());
    }

    /*
    |--------------------------------------------------------------------------
    | Relatórios
    |--------------------------------------------------------------------------
    */

    public function test_report_wallet_filter_now_works(): void
    {
        $other = Wallet::factory()->for($this->user)->create(['type' => 'savings']);
        $this->expense(['description' => 'Gasto na corrente', 'amount' => 10]);
        $this->expense(['wallet_id' => $other->id, 'description' => 'Gasto na poupanca', 'amount' => 20]);

        $this->actingAs($this->user)->get(route('reports.index', ['wallet' => $other->id]))
            ->assertOk()
            ->assertSee('Gasto na poupanca')
            ->assertDontSee('Gasto na corrente');
    }

    public function test_report_print_csv_and_email_are_professional_features(): void
    {
        $this->expense(['description' => 'Farmácia', 'amount' => 80]);

        $this->actingAs($this->user)->get(route('reports.print'))->assertRedirect(route('reports.index'));

        $pro = $this->professional($this->user);
        Mail::fake();

        $this->actingAs($pro)->get(route('reports.print'))->assertOk()->assertSee('Farmácia');
        $csv = $this->actingAs($pro)->get(route('reports.export'));
        $csv->assertOk();
        $this->assertStringContainsString('Farmácia', $csv->streamedContent());

        $this->actingAs($pro)->post(route('reports.email'))->assertSessionHas('success');
        Mail::assertQueued(FinancialReportMail::class, fn ($mail) => $mail->hasTo($pro->email));
    }
}
