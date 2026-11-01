<?php

namespace App\Repositories;

use App\DTOs\CategoryDTO;
use App\Interfaces\Repositories\CategoryRepositoryInterface;
use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CategoryRepository implements CategoryRepositoryInterface
{
    public function __construct(
        protected Category $model
    ) {}

    public function getAll(array $filters = []): Collection
    {
        return $this->model->orderBy('name')->get();
    }

    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->when(filled($filters['type'] ?? null), fn ($query) => $query->where('type', $filters['type']))
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function findById(string $id): ?Category
    {
        return $this->model->find($id);
    }

    public function create(CategoryDTO $dto): Category
    {
        return $this->model->create($dto->toArray());
    }

    public function update(string $id, CategoryDTO $dto): Category
    {
        $category = $this->findById($id);
        $category->update($dto->toArray());

        return $category->fresh();
    }

    public function delete(string $id): bool
    {
        $category = $this->findById($id);

        return $category instanceof Category ? $category->delete() : false;
    }

    public function namesForUser(string $userId, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return $this->model->newQuery()
            ->withoutGlobalScope('user')
            ->withTrashed()
            ->where('user_id', $userId)
            ->whereIn('id', $ids)
            ->get(['id', 'name', 'deleted_at'])
            ->mapWithKeys(fn (Category $category): array => [
                (string) $category->id => ['name' => (string) $category->name, 'deleted' => $category->trashed()],
            ])
            ->all();
    }
}
