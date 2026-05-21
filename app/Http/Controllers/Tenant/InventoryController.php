<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Catalog\Models\ProductBatch;
use App\Domain\Inventory\Models\StockMovement;
use App\Http\Controllers\Controller;
use App\Support\Catalog\EffectiveStorageLocation;
use Inertia\Inertia;
use Inertia\Response;

final class InventoryController extends Controller
{
    public function index(): Response
    {
        abort_unless(auth()->user()?->can('inventory.view'), 403);

        $lowStockBatches = ProductBatch::query()
            ->select('product_batches.*')
            ->join('products', 'products.id', '=', 'product_batches.product_id')
            ->whereRaw('product_batches.quantity_on_hand < products.min_stock')
            ->where('products.is_active', true)
            ->with(['product.storageLocation', 'storageLocation'])
            ->orderBy('product_batches.quantity_on_hand')
            ->limit(50)
            ->get()
            ->map(function (ProductBatch $batch) {
                $batch->setAttribute('effective_storage_location', EffectiveStorageLocation::forBatch($batch));

                return $batch;
            });

        $recentMovements = StockMovement::query()
            ->with('batch.product')
            ->orderByDesc('created_at')
            ->limit(40)
            ->get();

        $batchSummary = ProductBatch::query()
            ->with(['product.storageLocation', 'storageLocation'])
            ->orderByDesc('quantity_on_hand')
            ->paginate(25);

        $batchSummary->getCollection()->transform(function (ProductBatch $batch) {
            $batch->setAttribute('effective_storage_location', EffectiveStorageLocation::forBatch($batch));

            return $batch;
        });

        return Inertia::render('Inventory/Index', [
            'lowStockBatches' => $lowStockBatches,
            'recentMovements' => $recentMovements,
            'batches' => $batchSummary,
        ]);
    }
}
