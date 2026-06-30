<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductBatch;
use App\Domain\Catalog\Services\ProductService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\UpdateProductBatchMarkupRequest;
use App\Support\Tenant\TenantFeatures;
use Illuminate\Http\RedirectResponse;

final class ProductBatchController extends Controller
{
    public function __construct(private readonly ProductService $products) {}

    public function updateMarkup(
        UpdateProductBatchMarkupRequest $request,
        Product $product,
        ProductBatch $batch,
    ): RedirectResponse {
        abort_unless((int) $batch->product_id === (int) $product->getKey(), 404);

        $updates = [];

        if (TenantFeatures::markupPricingEnabled(tenant()) && $request->exists('markup_percent')) {
            $updates['markup_percent'] = $request->validated('markup_percent');
        }

        if ($request->exists('sale_price')) {
            $updates['sale_price'] = $request->validated('sale_price');
        }

        $batch->update($updates);

        if (array_key_exists('sale_price', $updates) && $updates['sale_price'] !== null) {
            $this->products->syncSalePricesFromBatch($product, $batch->fresh(), (float) $updates['sale_price']);
        }

        return back()->with('success', __('catalog.batch_pricing_updated'));
    }
}
