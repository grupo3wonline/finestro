<?php

namespace App\Interfaces\Services;

use App\DTOs\GoalDTO;
use App\Models\Goal;
use Illuminate\Database\Eloquent\Collection;

interface GoalServiceInterface
{
    public function getAllGoals(array $filters = []): Collection;

    public function getGoalById(string $id): ?Goal;

    public function createGoal(GoalDTO $dto): Goal;

    public function updateGoal(string $id, GoalDTO $dto): Goal;

    public function deleteGoal(string $id): bool;
}
