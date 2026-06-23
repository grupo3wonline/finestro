<?php

namespace App\Http\Controllers;

use App\Interfaces\Services\TransactionServiceInterface;
use App\Interfaces\Services\WalletServiceInterface;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(
        protected TransactionServiceInterface $transactionService,
        protected WalletServiceInterface $walletService
    ) {}

    public function index(Request $request): View
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->toDateString());
        $walletFilter = $request->get('wallet_id');
        $typeFilter = $request->get('type');

        $transactions = $this->transactionService->getTransactionsByDateRange($startDate, $endDate);

        // Apply additional filters
        if ($walletFilter) {
            $transactions = $transactions->where('wallet_id', $walletFilter);
        }
        if ($typeFilter) {
            $transactions = $transactions->where('type', $typeFilter);
        }

        $wallets = $this->walletService->getAllWallets();

        $totalIncome = $transactions->where('type', 'income')->sum('amount');
        $totalExpenses = $transactions->where('type', 'expense')->sum('amount');
        $balance = $totalIncome - $totalExpenses;
        $transactionCount = $transactions->count();

        // Group by category
        $byCategory = $transactions->groupBy(function ($t) {
            return $t->category ? $t->category->name : 'Sem Categoria';
        })->map(function ($group) {
            return [
                'total' => $group->sum('amount'),
                'count' => $group->count(),
                'type' => $group->first()->type,
            ];
        })->sortByDesc('total');

        // Group by wallet
        $byWallet = $transactions->groupBy(function ($t) {
            return $t->wallet ? $t->wallet->name : 'Sem Carteira';
        })->map(function ($group) {
            return [
                'income' => $group->where('type', 'income')->sum('amount'),
                'expense' => $group->where('type', 'expense')->sum('amount'),
                'count' => $group->count(),
            ];
        });

        // Daily totals for chart
        $dailyData = $transactions->groupBy(function ($t) {
            return $t->date->format('Y-m-d');
        })->map(function ($group) {
            return [
                'income' => $group->where('type', 'income')->sum('amount'),
                'expense' => $group->where('type', 'expense')->sum('amount'),
            ];
        })->sortKeys();

        // Top 5 expenses
        $topExpenses = $transactions->where('type', 'expense')
            ->sortByDesc('amount')
            ->take(5);

        // Monthly comparison (current period vs previous same-length period)
        $periodDays = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate));
        $prevStart = Carbon::parse($startDate)->subDays($periodDays + 1)->toDateString();
        $prevEnd = Carbon::parse($startDate)->subDay()->toDateString();
        $prevTransactions = $this->transactionService->getTransactionsByDateRange($prevStart, $prevEnd);

        $prevIncome = $prevTransactions->where('type', 'income')->sum('amount');
        $prevExpenses = $prevTransactions->where('type', 'expense')->sum('amount');

        $incomeChange = $prevIncome > 0 ? (($totalIncome - $prevIncome) / $prevIncome) * 100 : 0;
        $expenseChange = $prevExpenses > 0 ? (($totalExpenses - $prevExpenses) / $prevExpenses) * 100 : 0;

        // Expense categories for pie chart
        $expenseByCategory = $transactions->where('type', 'expense')->groupBy(function ($t) {
            return $t->category ? $t->category->name : 'Sem Categoria';
        })->map(fn($group) => $group->sum('amount'))->sortByDesc(fn($v) => $v);

        return view('reports.index', compact(
            'transactions',
            'wallets',
            'totalIncome',
            'totalExpenses',
            'balance',
            'transactionCount',
            'byCategory',
            'byWallet',
            'dailyData',
            'startDate',
            'endDate',
            'walletFilter',
            'typeFilter',
            'topExpenses',
            'incomeChange',
            'expenseChange',
            'expenseByCategory'
        ));
    }

    public function export(Request $request)
    {
        if (!auth()->user()->hasFeature('reports_export')) {
            return redirect()->route('reports.index')
                ->with('error', 'Exportação de relatórios disponível apenas no plano Profissional.');
        }

        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->toDateString());

        $transactions = $this->transactionService->getTransactionsByDateRange($startDate, $endDate);

        $filename = 'finestro_relatorio_' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($transactions) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM for UTF-8

            fputcsv($file, ['Data', 'Descrição', 'Tipo', 'Carteira', 'Categoria', 'Valor'], ';');

            foreach ($transactions as $t) {
                fputcsv($file, [
                    $t->date->format('d/m/Y'),
                    $t->description,
                    $t->type === 'income' ? 'Receita' : 'Despesa',
                    $t->wallet->name ?? '-',
                    $t->category->name ?? '-',
                    number_format($t->amount, 2, ',', '.'),
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
