<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\ReportFilterData;
use App\Enums\PaymentMethod;
use App\Exports\TransactionsExport;
use App\Interfaces\Services\ReportServiceInterface;
use App\Interfaces\Services\TransactionServiceInterface;
use App\Interfaces\Services\UserCacheInterface;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Single source of report data for the screen, print, PDF, e-mail and
 * exports.
 *
 * The transaction list (and the top expenses taken from it) is always read
 * live. Every other aggregate comes from summary(), which returns plain
 * arrays/scalars only and is cached per user (UserCacheInterface).
 *
 * @phpstan-type ReportSummary array{
 *     totalIncome: float,
 *     totalExpenses: float,
 *     balance: float,
 *     transactionCount: int,
 *     byCategory: array<array-key, array{total: float, count: int, type: mixed}>,
 *     byWallet: array<array-key, array{income: float, expense: float, count: int}>,
 *     dailyData: array<array-key, array{income: float, expense: float}>,
 *     incomeChange: float|int,
 *     expenseChange: float|int,
 *     expenseByCategory: array<array-key, float>,
 *     fixedVsVariable: array<string, array{fixed: float, variable: float}>,
 *     statusBreakdown: array<string, array{paid: float, pending: float}>,
 *     byPaymentMethod: array<array-key, float>
 * }
 */
class ReportService implements ReportServiceInterface
{
    public function __construct(
        protected TransactionServiceInterface $transactionService,
        protected UserCacheInterface $userCache
    ) {}

    public function build(User $user, ReportFilterData $filters): array
    {
        $transactions = $this->transactionsFor($filters);
        $summary = $this->summary($user, $filters, $transactions);

        $topExpenses = $transactions->where('type', 'expense')->sortByDesc('amount')->take(5);

        return [
            'transactions' => $transactions,
            'totalIncome' => $summary['totalIncome'],
            'totalExpenses' => $summary['totalExpenses'],
            'balance' => $summary['balance'],
            'transactionCount' => $summary['transactionCount'],
            'byCategory' => collect($summary['byCategory']),
            'byWallet' => collect($summary['byWallet']),
            'dailyData' => collect($summary['dailyData']),
            'startDate' => $filters->startDate,
            'endDate' => $filters->endDate,
            'walletFilter' => $filters->walletId,
            'typeFilter' => $filters->type,
            'statusFilter' => $filters->status,
            'topExpenses' => $topExpenses,
            'incomeChange' => $summary['incomeChange'],
            'expenseChange' => $summary['expenseChange'],
            'expenseByCategory' => collect($summary['expenseByCategory']),
            'fixedVsVariable' => $summary['fixedVsVariable'],
            'statusBreakdown' => $summary['statusBreakdown'],
            'byPaymentMethod' => collect($summary['byPaymentMethod']),
        ];
    }

    public function summaryFor(User $user, ReportFilterData $filters): array
    {
        $summary = $this->summary($user, $filters);

        return [
            'startDate' => $filters->startDate,
            'endDate' => $filters->endDate,
            'totalIncome' => $summary['totalIncome'],
            'totalExpenses' => $summary['totalExpenses'],
            'balance' => $summary['balance'],
            'fixedVsVariable' => $summary['fixedVsVariable'],
            'statusBreakdown' => $summary['statusBreakdown'],
            'expenseByCategory' => $summary['expenseByCategory'],
            'transactionCount' => $summary['transactionCount'],
        ];
    }

    public function exportCsv(BaseCollection $transactions): string
    {
        return Excel::raw(new TransactionsExport($transactions, ExcelWriter::CSV), ExcelWriter::CSV);
    }

    public function exportXlsx(BaseCollection $transactions): string
    {
        return Excel::raw(new TransactionsExport($transactions, ExcelWriter::XLSX), ExcelWriter::XLSX);
    }

    /*
    |--------------------------------------------------------------------------
    | Aggregation
    |--------------------------------------------------------------------------
    */

    /**
     * @return Collection<int, Transaction>
     */
    protected function transactionsFor(ReportFilterData $filters): Collection
    {
        return $this->filter(
            $this->transactionService->getTransactionsByDateRange($filters->startDate, $filters->endDate),
            $filters,
        );
    }

    /**
     * Cached aggregates of the period. $transactions is the already loaded
     * live list (avoids a second query on a cache miss).
     *
     * @param  Collection<int, Transaction>|null  $transactions
     * @return ReportSummary
     */
    protected function summary(User $user, ReportFilterData $filters, ?Collection $transactions = null): array
    {
        return $this->userCache->remember(
            (string) $user->id,
            'reports.summary',
            $filters->toCacheParams(),
            fn (): array => $this->computeSummary($filters, $transactions ?? $this->transactionsFor($filters)),
        );
    }

    /**
     * @param  Collection<int, Transaction>  $transactions
     * @return ReportSummary
     */
    protected function computeSummary(ReportFilterData $filters, Collection $transactions): array
    {
        $totalIncome = (float) $transactions->where('type', 'income')->sum('amount');
        $totalExpenses = (float) $transactions->where('type', 'expense')->sum('amount');

        $byCategory = $transactions->groupBy(fn ($t) => $t->category->name ?? 'Sem Categoria')
            ->map(fn ($group): array => [
                'total' => (float) $group->sum('amount'),
                'count' => $group->count(),
                'type' => $group->first()->type,
            ])->sortByDesc('total');

        $byWallet = $transactions->groupBy(fn ($t) => $t->wallet->name ?? 'Sem Carteira')
            ->map(fn ($group): array => [
                'income' => (float) $group->where('type', 'income')->sum('amount'),
                'expense' => (float) $group->where('type', 'expense')->sum('amount'),
                'count' => $group->count(),
            ]);

        $dailyData = $transactions->groupBy(fn ($t) => $t->date->format('Y-m-d'))
            ->map(fn ($group): array => [
                'income' => (float) $group->where('type', 'income')->sum('amount'),
                'expense' => (float) $group->where('type', 'expense')->sum('amount'),
            ])->sortKeys();

        // Comparison with the previous period of the same length.
        $periodDays = Carbon::parse($filters->startDate)->diffInDays(Carbon::parse($filters->endDate));
        $prevStart = Carbon::parse($filters->startDate)->subDays($periodDays + 1)->toDateString();
        $prevEnd = Carbon::parse($filters->startDate)->subDay()->toDateString();
        $prevTransactions = $this->filter(
            $this->transactionService->getTransactionsByDateRange($prevStart, $prevEnd),
            $filters,
        );
        $prevIncome = (float) $prevTransactions->where('type', 'income')->sum('amount');
        $prevExpenses = (float) $prevTransactions->where('type', 'expense')->sum('amount');
        $incomeChange = $prevIncome > 0 ? (($totalIncome - $prevIncome) / $prevIncome) * 100 : 0;
        $expenseChange = $prevExpenses > 0 ? (($totalExpenses - $prevExpenses) / $prevExpenses) * 100 : 0;

        $expenseByCategory = $transactions->where('type', 'expense')
            ->groupBy(fn ($t) => $t->category->name ?? 'Sem Categoria')
            ->map(fn ($group): float => (float) $group->sum('amount'))
            ->sortByDesc(fn ($v) => $v);

        // Fixed vs variable: fixed = recurring or installment.
        $isFixed = fn ($t): bool => $t->is_recurring || $t->isInstallment();
        $fixedVsVariable = [];
        foreach (['income', 'expense'] as $type) {
            $ofType = $transactions->where('type', $type);
            $fixedVsVariable[$type] = [
                'fixed' => (float) $ofType->filter($isFixed)->sum('amount'),
                'variable' => (float) $ofType->reject($isFixed)->sum('amount'),
            ];
        }

        // Realized vs pending.
        $statusBreakdown = [];
        foreach (['income', 'expense'] as $type) {
            $ofType = $transactions->where('type', $type);
            $statusBreakdown[$type] = [
                'paid' => (float) $ofType->where('status', 'paid')->sum('amount'),
                'pending' => (float) $ofType->where('status', 'pending')->sum('amount'),
            ];
        }

        // Expenses by payment method.
        $byPaymentMethod = $transactions->where('type', 'expense')
            ->groupBy(fn ($t) => PaymentMethod::tryFrom((string) $t->payment_method)?->label() ?? 'Não informada')
            ->map(fn ($group): float => (float) $group->sum('amount'))
            ->sortDesc();

        // Plain arrays only: cache stores never unserialize objects.
        return [
            'totalIncome' => $totalIncome,
            'totalExpenses' => $totalExpenses,
            'balance' => $totalIncome - $totalExpenses,
            'transactionCount' => $transactions->count(),
            'byCategory' => $byCategory->all(),
            'byWallet' => $byWallet->all(),
            'dailyData' => $dailyData->all(),
            'incomeChange' => $incomeChange,
            'expenseChange' => $expenseChange,
            'expenseByCategory' => $expenseByCategory->all(),
            'fixedVsVariable' => $fixedVsVariable,
            'statusBreakdown' => $statusBreakdown,
            'byPaymentMethod' => $byPaymentMethod->all(),
        ];
    }

    /**
     * @param  Collection<int, Transaction>  $transactions
     * @return Collection<int, Transaction>
     */
    protected function filter(Collection $transactions, ReportFilterData $filters): Collection
    {
        return $transactions
            ->when($filters->walletId, fn ($c) => $c->where('wallet_id', $filters->walletId))
            ->when($filters->type, fn ($c) => $c->where('type', $filters->type))
            ->when($filters->status, fn ($c) => $c->where('status', $filters->status))
            ->values();
    }
}
