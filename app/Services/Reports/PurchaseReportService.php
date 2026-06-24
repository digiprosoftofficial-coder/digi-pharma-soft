<?php

namespace App\Services\Reports;

use App\Domain\Purchasing\Models\Purchase;
use App\Domain\Purchasing\Models\PurchaseReturn;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class PurchaseReportService
{
    public function summary(ReportFilter $filter): array
    {
        $purchases = $this->purchasesQuery($filter);
        $returns = $this->returnsQuery($filter);
        $purchaseTotal = (float) (clone $purchases)->sum('total');
        $paid = (float) (clone $purchases)->sum('paid');
        $due = (float) (clone $purchases)->sum('due');
        $returnCredit = (float) (clone $returns)->sum('total_credit');

        return [
            'purchaseTotal' => $purchaseTotal,
            'paid' => $paid,
            'due' => $due,
            'returnCredit' => $returnCredit,
            'netPurchase' => max(0, $purchaseTotal - $returnCredit),
            'purchaseCount' => (clone $purchases)->count(),
            'returnCount' => (clone $returns)->count(),
        ];
    }

    public function purchases(ReportFilter $filter, int $perPage = 25): LengthAwarePaginator
    {
        return $this->purchasesQuery($filter)
            ->with(['supplier:id,name,phone', 'branch:id,name,code'])
            ->orderByDesc('purchased_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function topSuppliers(ReportFilter $filter): Collection
    {
        $query = Purchase::query()
            ->withoutGlobalScope('branch')
            ->select('supplier_id', DB::raw('SUM(total) as purchase_total'), DB::raw('SUM(due) as due_total'))
            ->with('supplier:id,name')
            ->where('status', 'posted')
            ->whereBetween('purchased_at', [$filter->dateFrom->toDateString(), $filter->dateTo->toDateString()]);

        if ($filter->branchId !== null) {
            $query->where('branch_id', $filter->branchId);
        }

        return $query
            ->groupBy('supplier_id')
            ->orderByDesc('purchase_total')
            ->limit(10)
            ->get()
            ->map(fn (Purchase $row) => [
                'supplier_id' => $row->supplier_id,
                'supplier_name' => $row->supplier?->name ?? 'Unknown supplier',
                'purchase_total' => (float) $row->purchase_total,
                'due_total' => (float) $row->due_total,
            ]);
    }

    public function exportRows(ReportFilter $filter): array
    {
        return $this->purchasesQuery($filter)
            ->with(['supplier:id,name', 'branch:id,name,code'])
            ->orderBy('purchased_at')
            ->get()
            ->map(fn (Purchase $purchase) => [
                $purchase->invoice_no,
                $purchase->purchased_at?->format('Y-m-d'),
                $purchase->branch?->name,
                $purchase->supplier?->name,
                (float) $purchase->total,
                (float) $purchase->paid,
                (float) $purchase->due,
                $purchase->status,
            ])
            ->all();
    }

    /**
     * @return Builder<Purchase>
     */
    private function purchasesQuery(ReportFilter $filter): Builder
    {
        $query = Purchase::query()
            ->withoutGlobalScope('branch')
            ->where('status', 'posted')
            ->whereBetween('purchased_at', [$filter->dateFrom->toDateString(), $filter->dateTo->toDateString()]);

        if ($filter->branchId !== null) {
            $query->where('branch_id', $filter->branchId);
        }

        return $query;
    }

    /**
     * @return Builder<PurchaseReturn>
     */
    private function returnsQuery(ReportFilter $filter): Builder
    {
        $query = PurchaseReturn::query()
            ->withoutGlobalScope('branch')
            ->where('status', 'posted')
            ->whereBetween('returned_at', [$filter->dateFrom, $filter->dateTo]);

        if ($filter->branchId !== null) {
            $query->where('branch_id', $filter->branchId);
        }

        return $query;
    }
}
