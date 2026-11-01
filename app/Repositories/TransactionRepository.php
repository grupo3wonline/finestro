<?php

namespace App\Repositories;

use App\DTOs\TransactionDTO;
use App\Interfaces\Repositories\TransactionRepositoryInterface;
use App\Models\Transaction;
use App\Support\Scout\SubqueryDatabaseEngine;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class TransactionRepository implements TransactionRepositoryInterface
{
    public function __construct(
        protected Transaction $model
    ) {}

    public function getAll(array $filters = []): Collection
    {
        return $this->filteredQuery($filters)->get();
    }

    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        // Media is eager-loaded for the receipt indicator (hasReceipt()).
        return $this->filteredQuery($filters)->with('media')->paginate($perPage);
    }

    public function filteredQuery(array $filters = []): Builder
    {
        // "overdue" não é um valor da coluna status: é pendente + vencida.
        $onlyOverdue = ($filters['status'] ?? null) === 'overdue';
        $columnFilters = $onlyOverdue ? array_diff_key($filters, ['status' => true]) : $filters;

        $query = $this->model->newQuery()
            ->with(['wallet', 'category'])
            // Campos simples (type, wallet_id, category_id, status, payment_method...)
            ->filter($columnFilters);

        if (filled($filters['search'] ?? null)) {
            $this->applySearch($query, (string) $filters['search']);
        }

        if ($onlyOverdue) {
            $query->overdue();
        }

        // Competência (mês/ano). "all" desativa o filtro de período.
        $year = $filters['year'] ?? null;
        $month = $filters['month'] ?? null;
        if ($year && $month && $month !== 'all') {
            $query->inMonth((int) $year, (int) $month);
        } elseif ($year && $month === 'all') {
            $query->whereYear('date', (int) $year);
        }

        if (! empty($filters['start_date'])) {
            $query->whereDate('date', '>=', $filters['start_date']);
        }
        if (! empty($filters['end_date'])) {
            $query->whereDate('date', '<=', $filters['end_date']);
        }

        return $query->orderBy('date', 'desc')->orderBy('created_at', 'desc');
    }

    /**
     * Restricts the query to the current user's transactions matching the term.
     * With the Scout database engine the match is an SQL subquery
     * (`id in (select ...)`), so no id list is loaded into PHP; other Scout
     * drivers fall back to the engine's key list. Scout does not escape LIKE
     * wildcards, so `\`, `%` and `_` are escaped here and match literally.
     *
     * @param  Builder<Transaction>  $query
     */
    private function applySearch(Builder $query, string $term): void
    {
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], trim($term));

        $scout = Transaction::search($escaped)
            ->query(fn (Builder $q) => $q->select('transactions.id'));

        // Scout\Builder::engine() is protected; the model resolves the same engine.
        $engine = $scout->model->searchableUsing();

        if ($engine instanceof SubqueryDatabaseEngine) {
            $query->whereIn('transactions.id', $engine->searchQuery($scout)->toBase());

            return;
        }

        $query->whereIn('transactions.id', $scout->keys()->all());
    }

    public function findById(string $id): ?Transaction
    {
        return $this->model->with(['wallet', 'category', 'media'])->find($id);
    }

    public function findTrashedById(string $id): ?Transaction
    {
        return $this->model->onlyTrashed()->with(['wallet', 'category'])->find($id);
    }

    public function create(TransactionDTO $dto): Transaction
    {
        return $this->model->create($dto->toArray());
    }

    public function update(string $id, TransactionDTO $dto): Transaction
    {
        $transaction = $this->findById($id);
        $transaction->update($dto->toArray(includeProvidedNulls: true));

        return $transaction->fresh(['wallet', 'category']);
    }

    public function delete(string $id): bool
    {
        $transaction = $this->findById($id);

        return $transaction instanceof Transaction ? $transaction->delete() : false;
    }

    public function getByWallet(string $walletId): Collection
    {
        return $this->model
            ->where('wallet_id', $walletId)
            ->with(['category'])
            ->orderBy('date', 'desc')
            ->get();
    }

    public function recentForWalletAcrossMembers(string $walletId, int $limit = 10): Collection
    {
        // Bypass the BelongsToUser scope so accepted members of a shared wallet
        // see every entry, not only their own. Access is authorized upstream.
        return $this->model->newQuery()
            ->withoutGlobalScope('user')
            ->where('wallet_id', $walletId)
            ->with(['category', 'user'])
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function getByDateRange(string $startDate, string $endDate): Collection
    {
        // whereDate normalises the column to a plain date, so an inclusive
        // upper bound keeps rows dated on the last day even when the driver
        // stores a time component (e.g. SQLite). A string BETWEEN would drop
        // them.
        return $this->model
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->with(['wallet', 'category'])
            ->orderBy('date', 'desc')
            ->get();
    }

    public function getByInstallmentGroup(string $groupId): Collection
    {
        return $this->model
            ->where('installment_group_id', $groupId)
            ->with(['wallet', 'category'])
            ->orderBy('installment_number')
            ->get();
    }

    public function getByWalletBetween(string $walletId, string $startDate, string $endDate): Collection
    {
        // See getByDateRange(): whereDate keeps the inclusive upper bound
        // correct regardless of a stored time component.
        return $this->model
            ->where('wallet_id', $walletId)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->with(['category'])
            ->orderBy('date')
            ->orderBy('created_at')
            ->get();
    }

    public function getTrashed(): Collection
    {
        return $this->model
            ->onlyTrashed()
            ->with(['wallet', 'category'])
            ->orderBy('deleted_at', 'desc')
            ->get();
    }

    public function getPendingUntil(string $type, string $until): Collection
    {
        return $this->model
            ->pending()
            ->where('type', $type)
            ->whereRaw('COALESCE(due_date, date) <= ?', [$until])
            ->with(['wallet', 'category'])
            ->orderByRaw('COALESCE(due_date, date) asc')
            ->get();
    }

    public function countPendingUntil(string $type, string $until): int
    {
        return $this->model
            ->pending()
            ->where('type', $type)
            ->whereRaw('COALESCE(due_date, date) <= ?', [$until])
            ->count();
    }
}
