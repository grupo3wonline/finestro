<?php

declare(strict_types=1);

namespace App\Interfaces\Repositories;

use App\DTOs\GoalDTO;
use App\Models\Goal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface GoalRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Goal>
     */
    public function getAll(array $filters = []): Collection;

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Goal>
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator;

    public function findById(string $id): ?Goal;

    public function create(GoalDTO $dto): Goal;

    public function update(string $id, GoalDTO $dto): Goal;

    public function delete(string $id): bool;
}
