<?php

namespace App\Repositories;

use App\DTOs\CategoryDTO;
use App\Interfaces\Repositories\CategoryRepositoryInterface;
use App\Models\Category;
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

        return $category ? $category->delete() : false;
    }
}
