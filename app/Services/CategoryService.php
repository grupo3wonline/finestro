<?php

namespace App\Services;

use App\DTOs\CategoryDTO;
use App\Interfaces\Repositories\CategoryRepositoryInterface;
use App\Interfaces\Services\CategoryServiceInterface;
use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class CategoryService implements CategoryServiceInterface
{
    public function __construct(
        protected CategoryRepositoryInterface $categoryRepository
    ) {}

    public function getAllCategories(array $filters = []): Collection
    {
        return $this->categoryRepository->getAll($filters);
    }

    public function getCategoryById(string $id): ?Category
    {
        return $this->categoryRepository->findById($id);
    }

    public function createCategory(CategoryDTO $dto): Category
    {
        return $this->categoryRepository->create($dto);
    }

    public function updateCategory(string $id, CategoryDTO $dto): Category
    {
        return $this->categoryRepository->update($id, $dto);
    }

    public function deleteCategory(string $id): bool
    {
        return $this->categoryRepository->delete($id);
    }
}
