<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Renderiza todas as telas autenticadas com os dados do seeder de demonstração.
 * Pega erros de Blade/variáveis que testes de regra de negócio não pegam.
 */
class PagesSmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $demo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->demo = User::where('email', 'demo@finestro.com.br')->firstOrFail();
    }

    /**
     * @return array<string, array{0: string, 1?: array<string, mixed>}>
     */
    public static function pages(): array
    {
        return [
            'dashboard' => ['dashboard'],
            'carteiras' => ['wallets.index'],
            'transações (mês atual)' => ['transactions.index'],
            'transações (ano todo)' => ['transactions.index', ['month' => 'all', 'year' => 2026]],
            'transações (busca)' => ['transactions.index', ['search' => 'Note']],
            'transações (atrasadas)' => ['transactions.index', ['status' => 'overdue']],
            'transações (filtros)' => ['transactions.index', ['type' => 'expense', 'status' => 'pending', 'payment_method' => 'boleto']],
            'nova transação' => ['transactions.create'],
            'agendar conta' => ['transactions.create', ['type' => 'expense', 'status' => 'pending']],
            'lixeira' => ['transactions.trash'],
            'contas a pagar' => ['bills.index'],
            'contas a receber' => ['bills.index', ['tipo' => 'receber']],
            'orçamentos' => ['budgets.index'],
            'orçamentos (mês vazio)' => ['budgets.index', ['month' => 1, 'year' => 2030]],
            'categorias' => ['categories.index'],
            'metas' => ['goals.index'],
            'relatórios' => ['reports.index'],
            'relatórios (filtros)' => ['reports.index', ['status' => 'pending', 'type' => 'expense']],
            'calculadoras' => ['calculators.index'],
            'simuladores' => ['simulators.index'],
            'configurações' => ['settings.index'],
            'assinatura' => ['subscription.index'],
        ];
    }

    #[DataProvider('pages')]
    public function test_page_renders(string $route, array $params = []): void
    {
        $this->actingAs($this->demo)->get(route($route, $params))->assertOk();
    }

    public function test_transaction_detail_edit_and_wallet_pages_render(): void
    {
        $this->actingAs($this->demo);

        $installment = Transaction::whereNotNull('installment_group_id')->orderBy('installment_number')->firstOrFail();
        $pending = Transaction::where('status', 'pending')->firstOrFail();

        $this->get(route('transactions.show', $installment))->assertOk()->assertSee('Parcelamento')->assertSee('1/10');
        $this->get(route('transactions.edit', $installment))->assertOk()->assertSee('Parcela 1/10');
        $this->get(route('transactions.show', $pending))->assertOk()->assertSee('Histórico');
        $this->get(route('transactions.edit', $pending))->assertOk();

        foreach (Wallet::all() as $wallet) {
            $this->get(route('wallets.show', $wallet))->assertOk()->assertSee('Últimos lançamentos');
        }

        $card = Wallet::where('type', 'credit')->firstOrFail();
        $this->get(route('wallets.invoice', $card))->assertOk()->assertSee('Notebook');
        $this->get(route('wallets.invoice', ['wallet' => $card->id, 'ref' => now()->addMonths(3)->format('Y-m')]))->assertOk();
    }

    public function test_dashboard_shows_bills_and_budget_alerts(): void
    {
        $response = $this->actingAs($this->demo)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Contas próximas')
            ->assertSee('Conta de luz')
            ->assertSee('atrasada(s)');

        // Força um orçamento estourado e confere o alerta.
        Budget::withoutGlobalScopes()->where('user_id', $this->demo->id)->whereNotNull('category_id')->update(['amount' => 1]);
        $this->actingAs($this->demo)->get(route('dashboard'))->assertOk()->assertSee('Orçamentos em alerta');
    }

    public function test_navigation_contains_new_sections(): void
    {
        $this->actingAs($this->demo)->get(route('dashboard'))
            ->assertSee(route('bills.index'), false)
            ->assertSee(route('budgets.index'), false);
    }
}
