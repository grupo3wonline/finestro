<?php

namespace App\Http\Controllers;

use App\DTOs\BudgetDTO;
use App\Http\Requests\StoreBudgetRequest;
use App\Interfaces\Services\BudgetServiceInterface;
use App\Interfaces\Services\CategoryServiceInterface;
use App\Models\Budget;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BudgetController extends Controller
{
    public function __construct(
        protected BudgetServiceInterface $budgetService,
        protected CategoryServiceInterface $categoryService
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Budget::class);

        [$year, $month] = $this->period($request);

        $budgets = $this->budgetService->getBudgetsWithUsage($year, $month);
        $overview = $this->budgetService->getMonthOverview($year, $month);
        $categories = $this->categoryService->getAllCategories()->where('type', 'expense')->values();

        $period = Carbon::create($year, $month, 1);
        $navigation = [
            'label' => ucfirst($period->locale('pt_BR')->translatedFormat('F \d\e Y')),
            'prev' => ['month' => $period->copy()->subMonth()->month, 'year' => $period->copy()->subMonth()->year],
            'next' => ['month' => $period->copy()->addMonth()->month, 'year' => $period->copy()->addMonth()->year],
        ];

        $limit = $request->user()->plan_limits['budgets_per_month'];
        $canAddMore = $budgets->count() < $limit;

        return view('budgets.index', ['budgets' => $budgets, 'overview' => $overview, 'categories' => $categories, 'navigation' => $navigation, 'year' => $year, 'month' => $month, 'canAddMore' => $canAddMore, 'limit' => $limit]);
    }

    public function store(StoreBudgetRequest $request): RedirectResponse
    {
        $this->authorize('create', Budget::class);

        $dto = BudgetDTO::fromArray($request->validated());
        $limit = $request->user()->plan_limits['budgets_per_month'];

        // Free plan: budgets per month limit (updating an existing one is always allowed).
        if (! $this->budgetService->canAddBudget($dto, $limit)) {
            return back()->with('error', 'O plano gratuito permite até '.$limit
                .' orçamentos por mês. Faça upgrade para o plano Profissional para criar orçamentos ilimitados.');
        }

        $saved = $this->budgetService->saveBudget($dto, $limit);

        return redirect()->route('budgets.index', ['month' => $dto->month, 'year' => $dto->year])
            ->with('success', $saved > 1 ? "Orçamento salvo para {$saved} meses." : 'Orçamento salvo com sucesso!');
    }

    public function update(StoreBudgetRequest $request, string $id): RedirectResponse
    {
        $budget = $this->budgetService->getBudgetById($id) ?? abort(404);
        $this->authorize('update', $budget);

        $this->budgetService->updateBudget(
            $id,
            BudgetDTO::fromArray($request->validated()),
            $request->user()->plan_limits['budgets_per_month']
        );

        return redirect()->route('budgets.index', ['month' => $budget->month, 'year' => $budget->year])
            ->with('success', 'Orçamento atualizado!');
    }

    public function destroy(string $id): RedirectResponse
    {
        $budget = $this->budgetService->getBudgetById($id) ?? abort(404);
        $this->authorize('delete', $budget);

        $this->budgetService->deleteBudget($id);

        return redirect()->route('budgets.index', ['month' => $budget->month, 'year' => $budget->year])
            ->with('success', 'Orçamento removido.');
    }

    public function copyPrevious(Request $request): RedirectResponse
    {
        $this->authorize('create', Budget::class);

        [$year, $month] = $this->period($request);

        $copied = $this->budgetService->copyFromPreviousMonth($year, $month, $request->user()->plan_limits['budgets_per_month']);

        return redirect()->route('budgets.index', ['month' => $month, 'year' => $year])
            ->with($copied ? 'success' : 'error', $copied
                ? "{$copied} orçamento(s) copiado(s) do mês anterior."
                : 'Não há orçamentos novos para copiar do mês anterior.');
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function period(Request $request): array
    {
        $data = $request->validate([
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
        ]);

        return [(int) ($data['year'] ?? now()->year), (int) ($data['month'] ?? now()->month)];
    }
}
