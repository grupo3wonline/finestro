<?php

declare(strict_types=1);

namespace App\Interfaces\Repositories;

use App\DTOs\CategoryDTO;
use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface CategoryRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Category>
     */
    public function getAll(array $filters = []): Collection;

    /**
     * Supported filter: `type` (income|expense).
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Category>
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator;

    public function findById(string $id): ?Category;

    public function create(CategoryDTO $dto): Category;

    public function update(string $id, CategoryDTO $dto): Category;

    public function delete(string $id): bool;

    /**
     * Names of the user's own categories (soft-deleted included), keyed by id.
     * Ids of other users' categories are left out.
     *
     * @param  list<string>  $ids
     * @return array<string, array{name: string, deleted: bool}>
     */
    public function namesForUser(string $userId, array $ids): array;
}
