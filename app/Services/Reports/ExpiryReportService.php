<?php

namespace App\Services\Reports;

use App\Domain\Catalog\Models\ProductBatch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class ExpiryReportService
{
    public function summary(ReportFilter $filter): array
    {
        $query = $this->query($filter);
        $today = now()->toDateString();

        return [
            'totalBatches' => (clone $query)->count(),
            'expired' => (clone $query)->whereDate('expiry_date', '<', $today)->count(),
            'expiring30' => (clone $query)->whereDate('expiry_date', '>=', $today)->whereDate('expiry_date', '<=', now()->addDays(30)->toDateString())->count(),
            'expiring90' => (clone $query)->whereDate('expiry_date', '>=', $today)->whereDate('expiry_date', '<=', now()->addDays(90)->toDateString())->count(),
            'stockAtRisk' => (float) (clone $query)->whereDate('expiry_date', '<=', now()->addDays(90)->toDateString())->sum('quantity_on_hand'),
        ];
    }

    public function rows(ReportFilter $filter, int $perPage = 25): LengthAwarePaginator
    {
        return $this->query($filter)
            ->with(['product:id,name,sku,category_id,manufacturer_id', 'product.category:id,name', 'product.manufacturer:id,name', 'branch:id,name,code', 'storageLocation:id,name'])
            ->orderBy('expiry_date')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function exportRows(ReportFilter $filter): array
    {
        return $this->query($filter)
            ->with(['product:id,name,sku', 'branch:id,name,code', 'storageLocation:id,name'])
            ->orderBy('expiry_date')
            ->get()
            ->map(fn (ProductBatch $batch) => [
                $batch->product?->name,
                $batch->product?->sku,
                $batch->batch_no,
                $batch->branch?->name,
                $batch->storageLocation?->name,
                (float) $batch->quantity_on_hand,
                $batch->expiry_date?->format('Y-m-d'),
                $this->expiryStatus($batch),
            ])
            ->all();
    }

    private function query(ReportFilter $filter): Builder
    {
        $query = ProductBatch::query()
            ->withoutGlobalScope('branch')
            ->where('quantity_on_hand', '>', 0)
            ->whereNotNull('expiry_date');

        if ($filter->branchId !== null) {
            $query->where('branch_id', $filter->branchId);
        }
        if ($filter->productId !== null) {
            $query->where('product_id', $filter->productId);
        }
        if ($filter->batch !== null) {
            $query->where('batch_no', 'like', '%'.$filter->batch.'%');
        }
        if ($filter->categoryId !== null || $filter->manufacturerId !== null) {
            $query->whereHas('product', function (Builder $q) use ($filter): void {
                if ($filter->categoryId !== null) {
                    $q->where('category_id', $filter->categoryId);
                }
                if ($filter->manufacturerId !== null) {
                    $q->where('manufacturer_id', $filter->manufacturerId);
                }
            });
        }

        return $this->applyExpiryStatus($query, $filter->expiryStatus);
    }

    private function applyExpiryStatus(Builder $query, ?string $status): Builder
    {
        $today = now()->toDateString();

        return match ($status) {
            'expired' => $query->whereDate('expiry_date', '<', $today),
            'expiring_30' => $query->whereDate('expiry_date', '>=', $today)->whereDate('expiry_date', '<=', now()->addDays(30)->toDateString()),
            'expiring_60' => $query->whereDate('expiry_date', '>=', $today)->whereDate('expiry_date', '<=', now()->addDays(60)->toDateString()),
            'expiring_90' => $query->whereDate('expiry_date', '>=', $today)->whereDate('expiry_date', '<=', now()->addDays(90)->toDateString()),
            'valid' => $query->whereDate('expiry_date', '>', now()->addDays(90)->toDateString()),
            default => $query,
        };
    }

    private function expiryStatus(ProductBatch $batch): string
    {
        if ($batch->expiry_date?->isPast()) {
            return 'Expired';
        }
        if ($batch->expiry_date && $batch->expiry_date->lte(now()->addDays(30))) {
            return 'Expiring soon';
        }

        return 'Valid';
    }
}
