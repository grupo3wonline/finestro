<?php

namespace App\Services;

use App\DTOs\GoalDTO;
use App\Interfaces\Repositories\GoalRepositoryInterface;
use App\Interfaces\Services\GoalServiceInterface;
use App\Models\Goal;
use Illuminate\Database\Eloquent\Collection;

class GoalService implements GoalServiceInterface
{
    public function __construct(
        protected GoalRepositoryInterface $goalRepository
    ) {}

    public function getAllGoals(array $filters = []): Collection
    {
        return $this->goalRepository->getAll($filters);
    }

    public function getGoalById(string $id): ?Goal
    {
        return $this->goalRepository->findById($id);
    }

    public function createGoal(GoalDTO $dto): Goal
    {
        return $this->goalRepository->create($dto);
    }

    public function updateGoal(string $id, GoalDTO $dto): Goal
    {
        return $this->goalRepository->update($id, $dto);
    }

    public function deleteGoal(string $id): bool
    {
        return $this->goalRepository->delete($id);
    }
}
