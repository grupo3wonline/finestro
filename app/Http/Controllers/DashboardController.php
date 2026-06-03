<?php

namespace App\Http\Controllers;

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
        protected GoalServiceInterface $goalService
    ) {}

    public function index(): View
    {
        $wallets = $this->walletService->getAllWallets();
        $goals = $this->goalService->getAllGoals(['status' => 'active']);

        $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
        $endOfMonth = Carbon::now()->endOfMonth()->toDateString();

        $transactions = $this->transactionService->getTransactionsByDateRange($startOfMonth, $endOfMonth);

        $totalBalance = $wallets->sum('balance');
        $totalIncome = $transactions->where('type', 'income')->sum('amount');
        $totalExpenses = $transactions->where('type', 'expense')->sum('amount');

        return view('dashboard.index', compact(
            'wallets',
            'goals',
            'transactions',
            'totalBalance',
            'totalIncome',
            'totalExpenses'
        ));
    }
}
