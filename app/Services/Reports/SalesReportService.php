<?php

namespace App\Services\Reports;

use App\Domain\Sales\Models\Sale;
use App\Domain\Sales\Models\SaleReturn;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class SalesReportService
{
    public function summary(ReportFilter $filter): array
    {
        $sales = $this->salesQuery($filter);
        $returns = $this->returnsQuery($filter);

        $grossSales = (float) (clone $sales)->sum(DB::raw('COALESCE(rounded_total, total)'));
        $paid = (float) (clone $sales)->sum('paid');
        $due = (float) (clone $sales)->sum('due');
        $returnTotal = (float) (clone $returns)->sum('total_refund');

        return [
            'grossSales' => $grossSales,
            'paid' => $paid,
            'due' => $due,
            'returnTotal' => $returnTotal,
            'netSales' => max(0, $grossSales - $returnTotal),
            'invoiceCount' => (clone $sales)->count(),
            'returnCount' => (clone $returns)->count(),
        ];
    }

    public function sales(ReportFilter $filter, int $perPage = 25): LengthAwarePaginator
    {
        return $this->salesQuery($filter)
            ->with(['customer:id,name,phone', 'branch:id,name,code'])
            ->orderByDesc('sold_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function topProducts(ReportFilter $filter): Collection
    {
        $query = DB::table('sale_lines')
            ->join('sales', 'sales.id', '=', 'sale_lines.sale_id')
            ->leftJoin('products', 'products.id', '=', 'sale_lines.product_id')
            ->select(
                'sale_lines.product_id',
                DB::raw("COALESCE(products.name, CONCAT('Product #', sale_lines.product_id)) as product_name"),
                DB::raw('SUM(sale_lines.quantity_base) as quantity'),
                DB::raw('SUM(sale_lines.line_total) as revenue'),
            )
            ->where('sale_lines.tenant_id', \tenant_id())
            ->where('sales.tenant_id', \tenant_id())
            ->where('sales.status', 'posted')
            ->whereBetween('sales.sold_at', [$filter->dateFrom, $filter->dateTo]);

        if ($filter->branchId !== null) {
            $query->where('sales.branch_id', $filter->branchId);
        }

        return $query
            ->groupBy('sale_lines.product_id', 'products.name')
            ->orderByDesc('quantity')
            ->limit(10)
            ->get();
    }

    public function exportRows(ReportFilter $filter): array
    {
        return $this->salesQuery($filter)
            ->with(['customer:id,name', 'branch:id,name,code'])
            ->orderBy('sold_at')
            ->get()
            ->map(fn (Sale $sale) => [
                $sale->invoice_no,
                $sale->sold_at?->format('Y-m-d H:i'),
                $sale->branch?->name,
                $sale->customer?->name,
                (float) ($sale->rounded_total ?? $sale->total),
                (float) $sale->paid,
                (float) $sale->due,
                $sale->status,
            ])
            ->all();
    }

    /**
     * @return Builder<Sale>
     */
    private function salesQuery(ReportFilter $filter): Builder
    {
        $query = Sale::query()
            ->withoutGlobalScope('branch')
            ->where('status', 'posted')
            ->whereBetween('sold_at', [$filter->dateFrom, $filter->dateTo]);

        if ($filter->branchId !== null) {
            $query->where('branch_id', $filter->branchId);
        }

        return $query;
    }

    /**
     * @return Builder<SaleReturn>
     */
    private function returnsQuery(ReportFilter $filter): Builder
    {
        $query = SaleReturn::query()
            ->withoutGlobalScope('branch')
            ->where('status', 'posted')
            ->whereBetween('returned_at', [$filter->dateFrom, $filter->dateTo]);

        if ($filter->branchId !== null) {
            $query->where('branch_id', $filter->branchId);
        }

        return $query;
    }
}
