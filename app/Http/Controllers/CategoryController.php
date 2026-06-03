<?php

namespace App\Http\Controllers;

use App\DTOs\CategoryDTO;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Interfaces\Services\CategoryServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryServiceInterface $categoryService
    ) {}

    public function index(Request $request): View
    {
        $categories = $this->categoryService->getAllCategories($request->only(['type']));

        return view('categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('categories.create');
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $user = auth()->user();
        if (!$user->canCreate('category')) {
            return redirect()->route('categories.index')
                ->with('error', 'Limite de categorias atingido. Faça upgrade para o plano Profissional.');
        }

        $dto = CategoryDTO::fromArray($request->validated());
        $this->categoryService->createCategory($dto);

        return redirect()->route('categories.index')
            ->with('success', 'Categoria criada com sucesso!');
    }

    public function edit(string $id): View
    {
        $category = $this->categoryService->getCategoryById($id);

        if (!$category) {
            abort(404);
        }

        return view('categories.edit', compact('category'));
    }

    public function update(UpdateCategoryRequest $request, string $id): RedirectResponse
    {
        $dto = CategoryDTO::fromArray($request->validated());
        $this->categoryService->updateCategory($id, $dto);

        return redirect()->route('categories.index')
            ->with('success', 'Categoria atualizada com sucesso!');
    }

    public function destroy(string $id): RedirectResponse
    {
        $this->categoryService->deleteCategory($id);

        return redirect()->route('categories.index')
            ->with('success', 'Categoria excluída com sucesso!');
    }
}
