<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Manufacturer;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Repositories\ProductRepository;
use App\Domain\Catalog\Services\ProductService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\StoreProductRequest;
use App\Http\Requests\Catalog\UpdateProductRequest;
use App\Http\Resources\Catalog\ProductResource;
use App\Support\Catalog\ProductCatalogOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly ProductRepository $products,
    ) {
        $this->authorizeResource(Product::class, 'product');
    }

    public function index(Request $request): Response
    {
        $filters = $request->only(['q', 'product_type', 'is_active']);

        return Inertia::render('Catalog/Products/Index', [
            'products' => $this->products->paginateForTenant($filters)->through(
                fn (Product $p) => (new ProductResource($p))->resolve($request),
            ),
            'filters' => $filters,
            'productTypes' => ProductCatalogOptions::productTypes(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Product::class);

        return Inertia::render('Catalog/Products/Form', [
            'product' => null,
            'catalogOptions' => $this->catalogOptions(),
            'categories' => $this->categoryOptions(),
            'manufacturers' => $this->manufacturerOptions(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $this->productService->createProduct($request->validated());

        return redirect()->route('tenant.products.index')->with('success', __('Product created.'));
    }

    public function edit(Product $product): Response
    {
        $this->authorize('update', $product);

        return Inertia::render('Catalog/Products/Form', [
            'product' => (new ProductResource($product->load(['category', 'manufacturer', 'batches', 'units'])))->resolve(request()),
            'catalogOptions' => $this->catalogOptions(),
            'categories' => $this->categoryOptions(),
            'manufacturers' => $this->manufacturerOptions(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $this->productService->updateProduct($product, $request->validated());

        return redirect()->route('tenant.products.index')->with('success', __('Product updated.'));
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);
        $product->delete();

        return redirect()->route('tenant.products.index')->with('success', __('Product removed.'));
    }

    /**
     * @return array{productTypes: list<string>, sellUnits: list<string>}
     */
    private function catalogOptions(): array
    {
        return [
            'productTypes' => ProductCatalogOptions::productTypes(),
            'sellUnits' => ProductCatalogOptions::sellUnits(),
        ];
    }

    /**
     * @return list<array{id:int,name:string}>
     */
    private function categoryOptions(): array
    {
        return Category::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Category $c) => ['id' => $c->id, 'name' => $c->name])
            ->all();
    }

    /**
     * @return list<array{id:int,name:string}>
     */
    private function manufacturerOptions(): array
    {
        return Manufacturer::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Manufacturer $m) => ['id' => $m->id, 'name' => $m->name])
            ->all();
    }
}
