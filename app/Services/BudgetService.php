<?php

namespace App\Services;

use App\DTOs\BudgetDTO;
use App\Interfaces\Repositories\BudgetRepositoryInterface;
use App\Interfaces\Services\BudgetServiceInterface;
use App\Models\Budget;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Orçamentos mensais.
 *
 * Ideia original: tabela `orcamento` do Financas-master (um teto por mês e a
 * opção de aplicar o novo valor "deste mês em diante"). Evoluções:
 *  - orçamento geral OU por categoria;
 *  - consumo calculado considerando despesas pagas + pendentes do mês
 *    (dinheiro já comprometido também conta);
 *  - alerta configurável (padrão 80%) e cópia do mês anterior.
 */
class BudgetService implements BudgetServiceInterface
{
    public function __construct(
        protected BudgetRepositoryInterface $budgetRepository
    ) {}

    public function getBudgetsWithUsage(int $year, int $month): Collection
    {
        $budgets = $this->budgetRepository->getForMonth($year, $month);
        $expenses = $this->budgetRepository->expensesByCategory($year, $month);
        $totalExpenses = array_sum($expenses);

        return $budgets->each(function (Budget $budget) use ($expenses, $totalExpenses): void {
            $spent = $budget->isGeneral()
                ? $totalExpenses
                : ($expenses[$budget->category_id] ?? 0.0);

            $amount = (float) $budget->amount;

            // Atributos calculados (não são colunas; o model não é salvo depois).
            $budget->setAttribute('spent', round($spent, 2));
            $budget->setAttribute('remaining', round($amount - $spent, 2));
            $budget->setAttribute('percent', $amount > 0 ? round(($spent / $amount) * 100, 1) : 0.0);
        });
    }

    public function getMonthOverview(int $year, int $month): array
    {
        $budgets = $this->getBudgetsWithUsage($year, $month);
        $expenses = $this->budgetRepository->expensesByCategory($year, $month);

        $general = $budgets->first(fn (Budget $b): bool => $b->isGeneral());
        $byCategory = $budgets->reject(fn (Budget $b): bool => $b->isGeneral());

        $budgetedCategoryIds = $byCategory->pluck('category_id')->all();
        $unbudgeted = collect($expenses)
            ->reject(fn ($total, $categoryId): bool => in_array($categoryId, $budgetedCategoryIds, true))
            ->sum();

        // Se há orçamento geral, ele é o teto do mês; senão, soma dos por categoria.
        $planned = $general ? (float) $general->amount : (float) $byCategory->sum('amount');
        $spent = (float) array_sum($expenses);

        return [
            'planned' => $planned,
            'spent' => $spent,
            'remaining' => $planned - $spent,
            'percent' => $planned > 0 ? round(($spent / $planned) * 100, 1) : 0.0,
            'unbudgeted' => (float) $unbudgeted,
        ];
    }

    public function getBudgetById(string $id): ?Budget
    {
        return $this->budgetRepository->findById($id);
    }

    public function saveBudget(BudgetDTO $dto, int $maxPerMonth = PHP_INT_MAX): int
    {
        return DB::transaction(function () use ($dto, $maxPerMonth): int {
            $lastMonth = $dto->repeatUntilDecember ? 12 : $dto->month;
            $saved = 0;

            for ($month = $dto->month; $month <= $lastMonth; $month++) {
                $data = array_merge($dto->toArray(), ['month' => $month]);
                $existing = $this->budgetRepository->findForPeriod($dto->categoryId, $dto->year, $month);

                if (! $existing && $this->budgetRepository->countForMonth($dto->year, $month) >= $maxPerMonth) {
                    continue;
                }

                $existing instanceof Budget
                    ? $this->budgetRepository->update($existing->id, $data)
                    : $this->budgetRepository->create($data);

                $saved++;
            }

            return $saved;
        });
    }

    public function updateBudget(string $id, BudgetDTO $dto, int $maxPerMonth = PHP_INT_MAX): Budget
    {
        return DB::transaction(function () use ($id, $dto, $maxPerMonth): Budget {
            $budget = $this->budgetRepository->update($id, [
                'amount' => $dto->amount,
                'alert_threshold' => $dto->alertThreshold ?? 80,
                'notes' => $dto->notes,
            ]);

            // "Aplicar deste mês em diante" (comportamento do Financas-master).
            if ($dto->repeatUntilDecember) {
                for ($month = $budget->month + 1; $month <= 12; $month++) {
                    $this->saveBudget(new BudgetDTO(
                        categoryId: $budget->category_id,
                        year: $budget->year,
                        month: $month,
                        amount: $dto->amount,
                        alertThreshold: $dto->alertThreshold,
                        notes: $dto->notes,
                    ), $maxPerMonth);
                }
            }

            return $budget;
        });
    }

    public function deleteBudget(string $id): bool
    {
        return $this->budgetRepository->delete($id);
    }

    public function canAddBudget(BudgetDTO $dto, int $maxPerMonth): bool
    {
        return $this->budgetRepository->findForPeriod($dto->categoryId, $dto->year, $dto->month) instanceof Budget
            || $this->budgetRepository->countForMonth($dto->year, $dto->month) < $maxPerMonth;
    }

    public function copyFromPreviousMonth(int $year, int $month, int $maxPerMonth = PHP_INT_MAX): int
    {
        $previous = Carbon::create($year, $month, 1)->subMonth();

        return DB::transaction(function () use ($previous, $year, $month, $maxPerMonth): int {
            $copied = 0;

            foreach ($this->budgetRepository->getForMonth($previous->year, $previous->month) as $budget) {
                if ($this->budgetRepository->findForPeriod($budget->category_id, $year, $month) instanceof Budget) {
                    continue; // não sobrescreve o que o usuário já definiu
                }

                if ($this->budgetRepository->countForMonth($year, $month) >= $maxPerMonth) {
                    break; // limite do plano atingido
                }

                $this->budgetRepository->create([
                    'category_id' => $budget->category_id,
                    'year' => $year,
                    'month' => $month,
                    'amount' => $budget->amount,
                    'alert_threshold' => $budget->alert_threshold,
                    'notes' => $budget->notes,
                ]);

                $copied++;
            }

            return $copied;
        });
    }

    public function getAlerts(int $year, int $month): Collection
    {
        return $this->getBudgetsWithUsage($year, $month)
            ->filter(fn (Budget $b): bool => $b->health !== 'ok')
            ->sortByDesc('percent')
            ->values();
    }
}
