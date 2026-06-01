<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductBatch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\UpdateProductBatchMarkupRequest;
use Illuminate\Http\RedirectResponse;

final class ProductBatchController extends Controller
{
    public function updateMarkup(
        UpdateProductBatchMarkupRequest $request,
        Product $product,
        ProductBatch $batch,
    ): RedirectResponse {
        abort_unless((int) $batch->product_id === (int) $product->getKey(), 404);

        $batch->update([
            'markup_percent' => $request->validated('markup_percent'),
        ]);

        return redirect()
            ->route('tenant.products.show', $product)
            ->with('success', __('catalog.batch_markup_updated'));
    }
}
