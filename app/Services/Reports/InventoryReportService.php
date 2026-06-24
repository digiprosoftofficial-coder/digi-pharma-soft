<?php

namespace App\Services\Reports;

use App\Domain\Catalog\Models\ProductBatch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class InventoryReportService
{
    public function summary(ReportFilter $filter): array
    {
        $batches = $this->batchesQuery($filter);
        $today = now()->toDateString();
        $expiringTo = now()->addDays(30)->toDateString();

        return [
            'batchCount' => (clone $batches)->count(),
            'stockQuantity' => (float) (clone $batches)->sum('quantity_on_hand'),
            'stockValue' => (float) (clone $batches)->sum(DB::raw('quantity_on_hand * purchase_unit_cost')),
            'lowStockCount' => (clone $batches)
                ->join('products', 'products.id', '=', 'product_batches.product_id')
                ->whereRaw('product_batches.quantity_on_hand < products.min_stock')
                ->where('products.is_active', true)
                ->count(),
            'expiredCount' => (clone $batches)
                ->where('quantity_on_hand', '>', 0)
                ->whereNotNull('expiry_date')
                ->whereDate('expiry_date', '<', $today)
                ->count(),
            'expiringSoonCount' => (clone $batches)
                ->where('quantity_on_hand', '>', 0)
                ->whereNotNull('expiry_date')
                ->whereDate('expiry_date', '>=', $today)
                ->whereDate('expiry_date', '<=', $expiringTo)
                ->count(),
        ];
    }

    public function batches(ReportFilter $filter, int $perPage = 25): LengthAwarePaginator
    {
        return $this->batchesQuery($filter)
            ->with(['product:id,name,sku,min_stock,is_active', 'storageLocation:id,name', 'branch:id,name,code'])
            ->orderByDesc('quantity_on_hand')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function lowStock(ReportFilter $filter): array
    {
        return $this->batchesQuery($filter)
            ->select('product_batches.*')
            ->join('products', 'products.id', '=', 'product_batches.product_id')
            ->whereRaw('product_batches.quantity_on_hand < products.min_stock')
            ->where('products.is_active', true)
            ->with(['product:id,name,sku,min_stock', 'branch:id,name,code'])
            ->orderBy('product_batches.quantity_on_hand')
            ->limit(10)
            ->get()
            ->all();
    }

    public function expiryRisk(ReportFilter $filter): array
    {
        return $this->batchesQuery($filter)
            ->where('quantity_on_hand', '>', 0)
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', now()->addDays(30)->toDateString())
            ->with(['product:id,name,sku', 'branch:id,name,code'])
            ->orderBy('expiry_date')
            ->limit(10)
            ->get()
            ->all();
    }

    public function exportRows(ReportFilter $filter): array
    {
        return $this->batchesQuery($filter)
            ->with(['product:id,name,sku,min_stock', 'storageLocation:id,name', 'branch:id,name,code'])
            ->orderBy('product_id')
            ->orderBy('expiry_date')
            ->get()
            ->map(fn (ProductBatch $batch) => [
                $batch->product?->name,
                $batch->product?->sku,
                $batch->batch_no,
                $batch->branch?->name,
                $batch->storageLocation?->name,
                (float) $batch->quantity_on_hand,
                (float) $batch->purchase_unit_cost,
                (float) $batch->quantity_on_hand * (float) $batch->purchase_unit_cost,
                $batch->expiry_date?->format('Y-m-d'),
                (float) ($batch->product?->min_stock ?? 0),
            ])
            ->all();
    }

    /**
     * @return Builder<ProductBatch>
     */
    private function batchesQuery(ReportFilter $filter): Builder
    {
        $query = ProductBatch::query()
            ->withoutGlobalScope('branch')
            ->where('quantity_on_hand', '>', 0);

        if ($filter->branchId !== null) {
            $query->where('branch_id', $filter->branchId);
        }

        return $query;
    }
}
