<?php

namespace App\Interfaces\Repositories;

use App\DTOs\GoalDTO;
use App\Models\Goal;
use Illuminate\Database\Eloquent\Collection;

interface GoalRepositoryInterface
{
    public function getAll(array $filters = []): Collection;

    public function findById(string $id): ?Goal;

    public function create(GoalDTO $dto): Goal;

    public function update(string $id, GoalDTO $dto): Goal;

    public function delete(string $id): bool;
}
