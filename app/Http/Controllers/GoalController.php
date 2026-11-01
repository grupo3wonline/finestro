<?php

namespace App\Http\Controllers;

use App\DTOs\GoalDTO;
use App\Http\Requests\StoreGoalRequest;
use App\Http\Requests\UpdateGoalRequest;
use App\Interfaces\Services\GoalServiceInterface;
use App\Models\Goal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GoalController extends Controller
{
    public function __construct(
        protected GoalServiceInterface $goalService
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Goal::class);

        $goals = $this->goalService->getAllGoals($request->only(['status']));

        return view('goals.index', ['goals' => $goals]);
    }

    public function create(): View
    {
        $this->authorize('create', Goal::class);

        return view('goals.create');
    }

    public function store(StoreGoalRequest $request): RedirectResponse
    {
        $user = auth()->user();
        if (! $user->canCreate('goal')) {
            return redirect()->route('goals.index')
                ->with('error', 'Limite de metas atingido. Faça upgrade para o plano Profissional.');
        }

        $dto = GoalDTO::fromArray($request->validated());
        $this->goalService->createGoal($dto);

        return redirect()->route('goals.index')
            ->with('success', 'Meta criada com sucesso!');
    }

    public function show(string $id): View
    {
        $goal = $this->goalService->getGoalById($id) ?? abort(404);
        $this->authorize('view', $goal);

        return view('goals.show', ['goal' => $goal]);
    }

    public function edit(string $id): View
    {
        $goal = $this->goalService->getGoalById($id) ?? abort(404);
        $this->authorize('update', $goal);

        return view('goals.edit', ['goal' => $goal]);
    }

    public function update(UpdateGoalRequest $request, string $id): RedirectResponse
    {
        // Scoped lookup: a foreign or missing id is a 404 (previously a 500).
        $this->authorize('update', $this->goalService->getGoalById($id) ?? abort(404));

        $dto = GoalDTO::fromArray($request->validated());
        $this->goalService->updateGoal($id, $dto);

        return redirect()->route('goals.index')
            ->with('success', 'Meta atualizada com sucesso!');
    }

    public function destroy(string $id): RedirectResponse
    {
        // Scoped lookup: a foreign or missing id is a 404 (previously a fake success).
        $this->authorize('delete', $this->goalService->getGoalById($id) ?? abort(404));

        $this->goalService->deleteGoal($id);

        return redirect()->route('goals.index')
            ->with('success', 'Meta excluída com sucesso!');
    }
}
