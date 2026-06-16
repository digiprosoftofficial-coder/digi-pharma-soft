<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Manufacturer;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\StorageLocation;
use App\Domain\Catalog\Repositories\ProductRepository;
use App\Domain\Catalog\Services\ProductService;
use App\Http\Controllers\Controller;
use App\Support\Catalog\ProductStockCalculator;
use App\Http\Requests\Catalog\StoreProductRequest;
use App\Http\Requests\Catalog\UpdateProductRequest;
use App\Http\Resources\Catalog\ProductResource;
use App\Support\Catalog\ProductCatalogOptions;
use App\Support\Catalog\ProductListPagination;
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
        $perPage = ProductListPagination::resolve(
            $request->has('per_page') ? $request->integer('per_page') : null,
        );

        $filters = array_merge(
            $request->only(['q', 'product_type', 'is_active', 'storage_location_id']),
            ['per_page' => $perPage],
        );

        return Inertia::render('Catalog/Products/Index', [
            'products' => $this->products->paginateForTenant($filters, $perPage)->through(
                fn (Product $p) => (new ProductResource($p))->resolve($request),
            ),
            'filters' => $filters,
            'productTypes' => ProductCatalogOptions::productTypes(),
            'storageLocations' => $this->storageLocationOptions(),
            'perPageOptions' => ProductListPagination::options(),
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
            'storageLocations' => $this->storageLocationOptions(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $this->productService->createProduct($request->validated(), $request->file('image'));

        return redirect()->route('tenant.products.index')->with('success', __('Product created.'));
    }

    public function show(Product $product): Response
    {
        $product->load([
            'category',
            'manufacturer',
            'storageLocation',
            'units',
            'batches' => fn ($q) => $q->with('storageLocation')->orderBy('expiry_date'),
        ]);

        $baseStock = $product->batches->sum(fn ($batch) => (float) $batch->quantity_on_hand);
        $purchasedQuantity = (float) $product->purchaseLines()->sum('quantity_base');

        $stockByUnit = $product->units->map(function ($unit) use ($baseStock) {
            $factor = max(0.0001, (float) $unit->conversion_factor);

            return [
                'sell_unit' => $unit->sell_unit,
                'conversion_factor' => (string) $unit->conversion_factor,
                'is_default' => (bool) $unit->is_default,
                'quantity_on_hand' => ProductStockCalculator::formatQuantity($baseStock / $factor),
            ];
        })->values()->all();

        $stockPieces = ProductStockCalculator::totalPieces($product, $baseStock);

        return Inertia::render('Catalog/Products/Show', [
            'product' => (new ProductResource($product))->resolve(request()),
            'stockBase' => ProductStockCalculator::formatQuantity($baseStock),
            'stockPieces' => $stockPieces !== null
                ? ProductStockCalculator::formatQuantity($stockPieces)
                : null,
            'purchasedQuantity' => ProductStockCalculator::formatQuantity($purchasedQuantity),
            'stockByUnit' => $stockByUnit,
        ]);
    }

    public function edit(Product $product): Response
    {
        $this->authorize('update', $product);

        return Inertia::render('Catalog/Products/Form', [
            'product' => (new ProductResource($product->load([
                'category',
                'manufacturer',
                'storageLocation',
                'batches.storageLocation',
                'units',
            ])))->resolve(request()),
            'catalogOptions' => $this->catalogOptions(),
            'categories' => $this->categoryOptions(),
            'manufacturers' => $this->manufacturerOptions(),
            'storageLocations' => $this->storageLocationOptions(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $this->productService->updateProduct($product, $request->validated(), $request->file('image'));

        return redirect()
            ->route('tenant.products.show', $product)
            ->with('success', __('Product updated.'));
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);
        $product->delete();

        return redirect()->route('tenant.products.index')->with('success', __('Product removed.'));
    }

    /**
     * @return array{productTypes: list<string>, productTypeOptions: list<array{slug: string, name: string, icon_url: string|null}>, sellUnits: list<string>}
     */
    private function catalogOptions(): array
    {
        return [
            'productTypes' => ProductCatalogOptions::productTypes(),
            'productTypeOptions' => ProductCatalogOptions::productTypeOptions(),
            'sellUnits' => ProductCatalogOptions::sellUnits(),
            'stripProductTypes' => ProductCatalogOptions::stripProductTypes(),
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

    /**
     * @return list<array{id:int,name:string,code:?string}>
     */
    private function storageLocationOptions(): array
    {
        return StorageLocation::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn (StorageLocation $loc) => [
                'id' => $loc->id,
                'name' => $loc->name,
                'code' => $loc->code,
            ])
            ->all();
    }
}
