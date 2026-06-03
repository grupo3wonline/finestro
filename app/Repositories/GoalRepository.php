<?php

namespace App\Repositories;

use App\DTOs\GoalDTO;
use App\Interfaces\Repositories\GoalRepositoryInterface;
use App\Models\Goal;
use Illuminate\Database\Eloquent\Collection;

class GoalRepository implements GoalRepositoryInterface
{
    public function __construct(
        protected Goal $model
    ) {}

    public function getAll(array $filters = []): Collection
    {
        return $this->model->filter($filters)->orderBy('deadline')->get();
    }

    public function findById(string $id): ?Goal
    {
        return $this->model->find($id);
    }

    public function create(GoalDTO $dto): Goal
    {
        return $this->model->create($dto->toArray());
    }

    public function update(string $id, GoalDTO $dto): Goal
    {
        $goal = $this->findById($id);
        $goal->update($dto->toArray());

        return $goal->fresh();
    }

    public function delete(string $id): bool
    {
        $goal = $this->findById($id);

        return $goal ? $goal->delete() : false;
    }
}
