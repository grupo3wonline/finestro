<?php

namespace App\Http\Controllers;

use App\DTOs\GoalDTO;
use App\Http\Requests\StoreGoalRequest;
use App\Http\Requests\UpdateGoalRequest;
use App\Interfaces\Services\GoalServiceInterface;
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
        $goals = $this->goalService->getAllGoals($request->only(['status']));

        return view('goals.index', compact('goals'));
    }

    public function create(): View
    {
        return view('goals.create');
    }

    public function store(StoreGoalRequest $request): RedirectResponse
    {
        $user = auth()->user();
        if (!$user->canCreate('goal')) {
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
        $goal = $this->goalService->getGoalById($id);

        if (!$goal) {
            abort(404);
        }

        return view('goals.show', compact('goal'));
    }

    public function edit(string $id): View
    {
        $goal = $this->goalService->getGoalById($id);

        if (!$goal) {
            abort(404);
        }

        return view('goals.edit', compact('goal'));
    }

    public function update(UpdateGoalRequest $request, string $id): RedirectResponse
    {
        $dto = GoalDTO::fromArray($request->validated());
        $this->goalService->updateGoal($id, $dto);

        return redirect()->route('goals.index')
            ->with('success', 'Meta atualizada com sucesso!');
    }

    public function destroy(string $id): RedirectResponse
    {
        $this->goalService->deleteGoal($id);

        return redirect()->route('goals.index')
            ->with('success', 'Meta excluída com sucesso!');
    }
}
