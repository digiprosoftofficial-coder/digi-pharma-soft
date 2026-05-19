<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Catalog\Models\CatalogProductType;
use App\Domain\Catalog\Services\ProductTypeService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\StoreProductTypeRequest;
use App\Http\Requests\Catalog\UpdateProductTypeRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class ProductTypeController extends Controller
{
    public function __construct(private readonly ProductTypeService $productTypes)
    {
        $this->authorizeResource(CatalogProductType::class, 'product_type');
    }

    public function index(): Response
    {
        return Inertia::render('Catalog/ProductTypes/Index', [
            'productTypes' => CatalogProductType::query()
                ->withCount('products')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate(20),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Catalog/ProductTypes/Form', ['productType' => null]);
    }

    public function store(StoreProductTypeRequest $request): RedirectResponse
    {
        $this->productTypes->create($request->validated());

        return redirect()->route('tenant.product-types.index')->with('success', __('Product type created.'));
    }

    public function edit(CatalogProductType $productType): Response
    {
        return Inertia::render('Catalog/ProductTypes/Form', ['productType' => $productType]);
    }

    public function update(UpdateProductTypeRequest $request, CatalogProductType $productType): RedirectResponse
    {
        $this->productTypes->update($productType, $request->validated());

        return redirect()->route('tenant.product-types.index')->with('success', __('Product type updated.'));
    }

    public function destroy(CatalogProductType $productType): RedirectResponse
    {
        $this->productTypes->delete($productType);

        return redirect()->route('tenant.product-types.index')->with('success', __('Product type removed.'));
    }
}
