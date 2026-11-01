<?php

declare(strict_types=1);

namespace App\Interfaces\Repositories;

use App\Models\Budget;
use Illuminate\Database\Eloquent\Collection;

interface BudgetRepositoryInterface
{
    /** @return Collection<int, Budget> */
    public function getForMonth(int $year, int $month): Collection;

    public function findById(string $id): ?Budget;

    public function findForPeriod(?string $categoryId, int $year, int $month): ?Budget;

    /** @param  array<string, mixed>  $data */
    public function create(array $data): Budget;

    /** @param  array<string, mixed>  $data */
    public function update(string $id, array $data): Budget;

    public function delete(string $id): bool;

    public function countForMonth(int $year, int $month): int;

    /**
     * Despesas (pagas + pendentes) do mês, somadas por categoria.
     * A chave '' (string vazia) representa transações sem categoria.
     *
     * @return array<string, float>
     */
    public function expensesByCategory(int $year, int $month): array;
}
