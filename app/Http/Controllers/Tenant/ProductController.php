<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Services\ProductService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\StoreProductRequest;
use App\Http\Requests\Catalog\UpdateProductRequest;
use App\Http\Resources\Catalog\ProductResource;
use App\Support\Catalog\ProductCatalogOptions;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
    ) {
        $this->authorizeResource(Product::class, 'product');
    }

    public function index(): Response
    {
        $products = Product::query()
            ->with(['category', 'manufacturer', 'batches', 'units'])
            ->orderByDesc('id')
            ->paginate(15);

        return Inertia::render('Catalog/Products/Index', [
            'products' => $products->through(
                fn (Product $p) => (new ProductResource($p))->resolve(request()),
            ),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Product::class);

        return Inertia::render('Catalog/Products/Form', [
            'product' => null,
            'catalogOptions' => $this->catalogOptions(),
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
            'product' => new ProductResource($product->load(['category', 'manufacturer', 'batches', 'units'])),
            'catalogOptions' => $this->catalogOptions(),
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
}
