<?php

namespace App\Http\Controllers;

use App\Interfaces\Services\BudgetServiceInterface;
use App\Interfaces\Services\GoalServiceInterface;
use App\Interfaces\Services\TransactionServiceInterface;
use App\Interfaces\Services\WalletServiceInterface;
use Carbon\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected WalletServiceInterface $walletService,
        protected TransactionServiceInterface $transactionService,
        protected GoalServiceInterface $goalService,
        protected BudgetServiceInterface $budgetService
    ) {}

    public function index(): View
    {
        $now = Carbon::now();

        $wallets = $this->walletService->getAllWallets();
        $goals = $this->goalService->getAllGoals(['status' => 'active']);

        $transactions = $this->transactionService->getTransactionsByDateRange(
            $now->copy()->startOfMonth()->toDateString(),
            $now->copy()->endOfMonth()->toDateString()
        );

        $totalBalance = $wallets->sum('balance');

        // Receitas/despesas do mês: realizadas (pagas) — as pendentes aparecem à parte.
        $summary = $this->transactionService->getSummary(['year' => $now->year, 'month' => (string) $now->month]);
        $totalIncome = $summary['income_paid'];
        $totalExpenses = $summary['expense_paid'];

        // Contas: atrasadas + vencendo nos próximos 7 dias (a pagar e a receber).
        $payables = $this->transactionService->getBills('expense', 7);
        $receivables = $this->transactionService->getBills('income', 7);
        $upcomingBills = collect()
            ->merge($payables['overdue'])->merge($payables['today'])->merge($payables['next7'])
            ->merge($receivables['overdue'])->merge($receivables['today'])->merge($receivables['next7'])
            ->sortBy(fn ($t) => $t->effective_due_date)
            ->values();
        $overdueCount = $payables['overdue']->count() + $receivables['overdue']->count();

        $budgetAlerts = $this->budgetService->getAlerts($now->year, $now->month);

        return view('dashboard.index', ['wallets' => $wallets, 'goals' => $goals, 'transactions' => $transactions, 'totalBalance' => $totalBalance, 'totalIncome' => $totalIncome, 'totalExpenses' => $totalExpenses, 'summary' => $summary, 'upcomingBills' => $upcomingBills, 'overdueCount' => $overdueCount, 'budgetAlerts' => $budgetAlerts] + ['greeting' => $this->greeting($now)]);
    }

    /**
     * Saudação conforme o horário (ideia do dashboard do Financas-master).
     */
    private function greeting(Carbon $now): string
    {
        return match (true) {
            $now->hour < 5 => 'Boa madrugada',
            $now->hour < 12 => 'Bom dia',
            $now->hour < 18 => 'Boa tarde',
            default => 'Boa noite',
        };
    }
}
