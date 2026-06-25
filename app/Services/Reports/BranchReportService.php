<?php

namespace App\Services\Reports;

use App\Domain\Tenant\Models\Branch;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class BranchReportService
{
    public function summary(ReportFilter $filter): array
    {
        $rows = $this->branchRows($filter);

        return [
            'branches' => $rows->count(),
            'sales' => (float) $rows->sum('sales_total'),
            'purchases' => (float) $rows->sum('purchase_total'),
            'stockValue' => (float) $rows->sum('stock_value'),
            'expiryRisk' => (int) $rows->sum('expiry_risk'),
        ];
    }

    public function rows(ReportFilter $filter, int $perPage = 25): LengthAwarePaginator
    {
        $rows = $this->branchRows($filter)->values();
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
        return $this->branchRows($filter)
            ->map(fn (array $row) => [
                $row['branch_name'],
                $row['branch_code'],
                $row['sales_total'],
                $row['purchase_total'],
                $row['sales_due'],
                $row['purchase_due'],
                $row['stock_value'],
                $row['expiry_risk'],
                $row['transfers_out'],
                $row['transfers_in'],
            ])
            ->values()
            ->all();
    }

    private function branchRows(ReportFilter $filter): Collection
    {
        $branches = Branch::query()
            ->when($filter->branchId, fn ($q) => $q->whereKey($filter->branchId))
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return $branches->map(function (Branch $branch) use ($filter) {
            $branchId = $branch->getKey();

            return [
                'branch_id' => $branchId,
                'branch_name' => $branch->name,
                'branch_code' => $branch->code,
                'sales_total' => (float) DB::table('sales')
                    ->where('tenant_id', \tenant_id())
                    ->where('branch_id', $branchId)
                    ->where('status', 'posted')
                    ->whereBetween('sold_at', [$filter->dateFrom, $filter->dateTo])
                    ->sum('total'),
                'purchase_total' => (float) DB::table('purchases')
                    ->where('tenant_id', \tenant_id())
                    ->where('branch_id', $branchId)
                    ->where('status', 'posted')
                    ->whereBetween('purchased_at', [$filter->dateFrom->toDateString(), $filter->dateTo->toDateString()])
                    ->sum('total'),
                'sales_due' => (float) DB::table('sales')
                    ->where('tenant_id', \tenant_id())
                    ->where('branch_id', $branchId)
                    ->where('status', 'posted')
                    ->sum('due'),
                'purchase_due' => (float) DB::table('purchases')
                    ->where('tenant_id', \tenant_id())
                    ->where('branch_id', $branchId)
                    ->where('status', 'posted')
                    ->sum('due'),
                'stock_value' => (float) DB::table('product_batches')
                    ->where('tenant_id', \tenant_id())
                    ->where('branch_id', $branchId)
                    ->sum(DB::raw('quantity_on_hand * purchase_unit_cost')),
                'expiry_risk' => (int) DB::table('product_batches')
                    ->where('tenant_id', \tenant_id())
                    ->where('branch_id', $branchId)
                    ->where('quantity_on_hand', '>', 0)
                    ->whereNotNull('expiry_date')
                    ->whereDate('expiry_date', '<=', now()->addDays(30)->toDateString())
                    ->count(),
                'transfers_out' => (int) DB::table('stock_transfers')
                    ->where('tenant_id', \tenant_id())
                    ->where('from_branch_id', $branchId)
                    ->whereBetween('transferred_at', [$filter->dateFrom, $filter->dateTo])
                    ->count(),
                'transfers_in' => (int) DB::table('stock_transfers')
                    ->where('tenant_id', \tenant_id())
                    ->where('to_branch_id', $branchId)
                    ->whereBetween('transferred_at', [$filter->dateFrom, $filter->dateTo])
                    ->count(),
            ];
        });
    }
}
