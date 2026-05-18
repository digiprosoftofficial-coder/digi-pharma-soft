<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Services\CategoryService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\StoreCategoryRequest;
use App\Http\Requests\Catalog\UpdateCategoryRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $categories)
    {
        $this->authorizeResource(Category::class, 'category');
    }

    public function index(): Response
    {
        return Inertia::render('Catalog/Categories/Index', [
            'categories' => Category::query()->withCount('products')->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Catalog/Categories/Form', ['category' => null]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $this->categories->create($request->validated());

        return redirect()->route('tenant.categories.index')->with('success', __('Category created.'));
    }

    public function edit(Category $category): Response
    {
        return Inertia::render('Catalog/Categories/Form', ['category' => $category]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $this->categories->update($category, $request->validated());

        return redirect()->route('tenant.categories.index')->with('success', __('Category updated.'));
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->categories->delete($category);

        return redirect()->route('tenant.categories.index')->with('success', __('Category removed.'));
    }
}
