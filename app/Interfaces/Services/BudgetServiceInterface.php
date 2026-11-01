<?php

declare(strict_types=1);

namespace App\Interfaces\Services;

use App\DTOs\BudgetDTO;
use App\Models\Budget;
use Illuminate\Database\Eloquent\Collection;

interface BudgetServiceInterface
{
    /**
     * Orçamentos do mês com consumo calculado (spent, remaining, percent).
     *
     * @return Collection<int, Budget>
     */
    public function getBudgetsWithUsage(int $year, int $month): Collection;

    /**
     * Totais do mês: orçado, gasto, restante e gastos sem orçamento.
     *
     * @return array<string, float>
     */
    public function getMonthOverview(int $year, int $month): array;

    public function getBudgetById(string $id): ?Budget;

    /**
     * Cria (ou atualiza, se já existir para o período/categoria).
     * Com repeatUntilDecember, replica até dezembro do mesmo ano.
     * Meses que já atingiram $maxPerMonth orçamentos são pulados (limite do plano).
     *
     * @return int quantidade de meses gravados
     */
    public function saveBudget(BudgetDTO $dto, int $maxPerMonth = PHP_INT_MAX): int;

    public function updateBudget(string $id, BudgetDTO $dto, int $maxPerMonth = PHP_INT_MAX): Budget;

    public function deleteBudget(string $id): bool;

    /**
     * Plan limit check for creating a budget: updating an existing budget for
     * the same period/category is always allowed; otherwise the month must be
     * below $maxPerMonth budgets.
     */
    public function canAddBudget(BudgetDTO $dto, int $maxPerMonth): bool;

    /**
     * Copia os orçamentos do mês anterior para o mês informado
     * (sem sobrescrever os que já existem).
     */
    public function copyFromPreviousMonth(int $year, int $month, int $maxPerMonth = PHP_INT_MAX): int;

    /**
     * Orçamentos do mês em alerta ou estourados (para o dashboard).
     *
     * @return Collection<int, Budget>
     */
    public function getAlerts(int $year, int $month): Collection;
}
