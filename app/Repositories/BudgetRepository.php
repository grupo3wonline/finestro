<?php

namespace App\Repositories;

use App\Interfaces\Repositories\BudgetRepositoryInterface;
use App\Models\Budget;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Collection;

class BudgetRepository implements BudgetRepositoryInterface
{
    public function __construct(
        protected Budget $model
    ) {}

    public function getForMonth(int $year, int $month): Collection
    {
        return $this->model
            ->with('category')
            ->where('year', $year)
            ->where('month', $month)
            // Orçamento geral (category_id NULL) primeiro.
            ->orderByRaw('category_id IS NOT NULL')
            ->orderBy('amount', 'desc')
            ->get();
    }

    public function findById(string $id): ?Budget
    {
        return $this->model->with('category')->find($id);
    }

    public function findForPeriod(?string $categoryId, int $year, int $month): ?Budget
    {
        return $this->model
            ->where('year', $year)
            ->where('month', $month)
            ->when(
                $categoryId,
                fn ($q) => $q->where('category_id', $categoryId),
                fn ($q) => $q->whereNull('category_id')
            )
            ->first();
    }

    public function create(array $data): Budget
    {
        return $this->model->create($data);
    }

    public function update(string $id, array $data): Budget
    {
        $budget = $this->findById($id);
        $budget->update($data);

        return $budget->fresh('category');
    }

    public function delete(string $id): bool
    {
        $budget = $this->findById($id);

        return $budget instanceof Budget && (bool) $budget->delete();
    }

    public function countForMonth(int $year, int $month): int
    {
        return $this->model->where('year', $year)->where('month', $month)->count();
    }

    public function expensesByCategory(int $year, int $month): array
    {
        // whereYear/whereMonth compare the date parts, so expenses dated on the
        // month's last day are counted even when the column carries a time
        // component (a string BETWEEN upper bound would drop them).
        return Transaction::query()
            ->where('type', 'expense')
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->toBase()
            ->get()
            ->mapWithKeys(fn ($row): array => [(string) ($row->category_id ?? '') => (float) $row->total])
            ->all();
    }
}
