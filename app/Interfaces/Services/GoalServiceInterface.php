<?php

declare(strict_types=1);

namespace App\Interfaces\Services;

use App\DTOs\GoalDTO;
use App\Models\Goal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface GoalServiceInterface
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Goal>
     */
    public function getAllGoals(array $filters = []): Collection;

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Goal>
     */
    public function getPaginatedGoals(array $filters, int $perPage): LengthAwarePaginator;

    public function getGoalById(string $id): ?Goal;

    public function createGoal(GoalDTO $dto): Goal;

    public function updateGoal(string $id, GoalDTO $dto): Goal;

    public function deleteGoal(string $id): bool;
}
