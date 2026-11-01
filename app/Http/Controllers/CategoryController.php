<?php

namespace App\Http\Controllers;

use App\DTOs\CategoryDTO;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Interfaces\Services\CategoryServiceInterface;
use App\Models\Category;
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
        $this->authorize('viewAny', Category::class);

        $categories = $this->categoryService->getAllCategories($request->only(['type']));

        return view('categories.index', ['categories' => $categories]);
    }

    public function create(): View
    {
        $this->authorize('create', Category::class);

        return view('categories.create');
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $user = auth()->user();
        if (! $user->canCreate('category')) {
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
        $category = $this->categoryService->getCategoryById($id) ?? abort(404);
        $this->authorize('update', $category);

        return view('categories.edit', ['category' => $category]);
    }

    public function update(UpdateCategoryRequest $request, string $id): RedirectResponse
    {
        // Scoped lookup: a foreign or missing id is a 404 (previously a 500).
        $this->authorize('update', $this->categoryService->getCategoryById($id) ?? abort(404));

        $dto = CategoryDTO::fromArray($request->validated());
        $this->categoryService->updateCategory($id, $dto);

        return redirect()->route('categories.index')
            ->with('success', 'Categoria atualizada com sucesso!');
    }

    public function destroy(string $id): RedirectResponse
    {
        // Scoped lookup: a foreign or missing id is a 404 (previously a fake success).
        $this->authorize('delete', $this->categoryService->getCategoryById($id) ?? abort(404));

        $this->categoryService->deleteCategory($id);

        return redirect()->route('categories.index')
            ->with('success', 'Categoria excluída com sucesso!');
    }

    /**
     * Importa as categorias sugeridas (lista do ebfinance) que o usuário ainda não tem.
     */
    public function importSuggested(Request $request): RedirectResponse
    {
        $this->authorize('create', Category::class);

        [$imported, $skipped] = $this->categoryService->importSuggested($request->user());

        if ($imported === 0 && $skipped === 0) {
            return redirect()->route('categories.index')
                ->with('success', 'Você já possui todas as categorias sugeridas.');
        }

        $message = "{$imported} categoria(s) importada(s).";

        if ($skipped > 0) {
            $message .= " {$skipped} não foram importadas por causa do limite do plano gratuito.";
        }

        return redirect()->route('categories.index')
            ->with($imported > 0 ? 'success' : 'error', $message);
    }
}
