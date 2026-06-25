<?php

namespace App\Services\Reports;

use App\Domain\Purchasing\Models\Purchase;
use App\Domain\Purchasing\Models\PurchaseReturn;
use App\Domain\Purchasing\Models\Supplier;
use App\Domain\Purchasing\Services\SupplierDueService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final readonly class SupplierReportService
{
    public function __construct(private SupplierDueService $dues) {}

    public function summary(ReportFilter $filter): array
    {
        $purchases = $this->purchaseRows($filter);

        return [
            'supplierCount' => $purchases->count(),
            'purchaseTotal' => (float) $purchases->sum('purchase_total'),
            'paid' => (float) $purchases->sum('paid_total'),
            'due' => (float) $purchases->sum('due_total'),
            'returnCredit' => (float) $purchases->sum('return_credit'),
        ];
    }

    public function rows(ReportFilter $filter, int $perPage = 25): LengthAwarePaginator
    {
        $rows = $this->purchaseRows($filter)->values();
        $page = LengthAwarePaginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()],
        );
    }

    public function exportRows(ReportFilter $filter): array
    {
        return $this->purchaseRows($filter)
            ->map(fn (array $row) => [
                $row['supplier_name'],
                $row['phone'],
                $row['purchase_count'],
                $row['purchase_total'],
                $row['paid_total'],
                $row['due_total'],
                $row['return_credit'],
            ])
            ->values()
            ->all();
    }

    private function purchaseRows(ReportFilter $filter): Collection
    {
        $suppliers = Supplier::query()
            ->when($filter->supplierId, fn ($q) => $q->whereKey($filter->supplierId))
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);

        return $suppliers->map(function (Supplier $supplier) use ($filter) {
            $purchaseQuery = Purchase::query()
                ->withoutGlobalScope('branch')
                ->where('supplier_id', $supplier->getKey())
                ->where('status', 'posted')
                ->whereBetween('purchased_at', [$filter->dateFrom->toDateString(), $filter->dateTo->toDateString()]);

            $returnQuery = PurchaseReturn::query()
                ->withoutGlobalScope('branch')
                ->where('supplier_id', $supplier->getKey())
                ->where('status', 'posted')
                ->whereBetween('returned_at', [$filter->dateFrom, $filter->dateTo]);

            if ($filter->branchId !== null) {
                $purchaseQuery->where('branch_id', $filter->branchId);
                $returnQuery->where('branch_id', $filter->branchId);
            }
            if ($filter->paymentStatus === 'paid') {
                $purchaseQuery->where('due', '<=', 0);
            } elseif ($filter->paymentStatus === 'due') {
                $purchaseQuery->where('due', '>', 0);
            } elseif ($filter->paymentStatus === 'partial') {
                $purchaseQuery->where('paid', '>', 0)->where('due', '>', 0);
            }

            $purchaseTotal = (float) (clone $purchaseQuery)->sum('total');
            $paidTotal = (float) (clone $purchaseQuery)->sum('paid');
            $dueTotal = $filter->branchId === null
                ? $this->dues->totalDue($supplier)
                : $this->dues->branchDue($supplier, $filter->branchId);
            $returnCredit = (float) $returnQuery->sum('total_credit');

            return [
                'supplier_id' => $supplier->getKey(),
                'supplier_name' => $supplier->name,
                'phone' => $supplier->phone,
                'purchase_count' => (clone $purchaseQuery)->count(),
                'purchase_total' => $purchaseTotal,
                'paid_total' => $paidTotal,
                'due_total' => $dueTotal,
                'return_credit' => $returnCredit,
            ];
        })->filter(function (array $row) use ($filter) {
            if ($filter->dueStatus === 'has_due') {
                return $row['due_total'] > 0;
            }
            if ($filter->dueStatus === 'clear') {
                return $row['due_total'] <= 0 && $row['purchase_count'] > 0;
            }

            return $row['purchase_count'] > 0 || $row['due_total'] > 0 || $row['return_credit'] > 0;
        })->sortByDesc('purchase_total')->values();
    }
}
