<?php

namespace App\Services\Reports;

use App\Domain\Accounting\Models\LedgerEntry;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class FinancialReportService
{
    public function summary(ReportFilter $filter): array
    {
        $entries = $this->entriesQuery($filter);
        $debits = (float) (clone $entries)->where('direction', 'debit')->sum('amount');
        $credits = (float) (clone $entries)->where('direction', 'credit')->sum('amount');

        return [
            'debits' => $debits,
            'credits' => $credits,
            'net' => $credits - $debits,
            'entryCount' => (clone $entries)->count(),
            'salesPayments' => $this->salesPayments($filter),
            'purchasePayments' => $this->purchasePayments($filter),
        ];
    }

    public function entries(ReportFilter $filter, int $perPage = 25): LengthAwarePaginator
    {
        return $this->entriesQuery($filter)
            ->with('account:id,code,name,type')
            ->orderByDesc('posted_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function paymentBreakdown(ReportFilter $filter): array
    {
        return [
            'sales' => DB::table('sale_payments')
                ->join('sales', 'sales.id', '=', 'sale_payments.sale_id')
                ->select('sale_payments.method', DB::raw('SUM(sale_payments.amount) as amount'))
                ->where('sale_payments.tenant_id', \tenant_id())
                ->whereBetween('sales.sold_at', [$filter->dateFrom, $filter->dateTo])
                ->when($filter->branchId, fn ($q) => $q->where('sales.branch_id', $filter->branchId))
                ->when($filter->paymentMethod, fn ($q) => $q->where('sale_payments.method', $filter->paymentMethod))
                ->groupBy('sale_payments.method')
                ->get(),
            'purchases' => DB::table('purchase_payments')
                ->join('purchases', 'purchases.id', '=', 'purchase_payments.purchase_id')
                ->select('purchase_payments.method', DB::raw('SUM(purchase_payments.amount) as amount'))
                ->where('purchase_payments.tenant_id', \tenant_id())
                ->whereBetween('purchase_payments.paid_at', [$filter->dateFrom->toDateString(), $filter->dateTo->toDateString()])
                ->when($filter->branchId, fn ($q) => $q->where('purchase_payments.paying_branch_id', $filter->branchId))
                ->when($filter->paymentMethod, fn ($q) => $q->where('purchase_payments.method', $filter->paymentMethod))
                ->groupBy('purchase_payments.method')
                ->get(),
        ];
    }

    public function exportRows(ReportFilter $filter): array
    {
        return $this->entriesQuery($filter)
            ->with('account:id,code,name,type')
            ->orderBy('posted_at')
            ->get()
            ->map(fn (LedgerEntry $entry) => [
                $entry->posted_at?->format('Y-m-d H:i'),
                $entry->account?->code,
                $entry->account?->name,
                $entry->account?->type,
                $entry->direction,
                (float) $entry->amount,
                $entry->memo,
                $entry->reference_type,
                $entry->reference_id,
            ])
            ->all();
    }

    private function entriesQuery(ReportFilter $filter): Builder
    {
        return LedgerEntry::query()
            ->whereBetween('posted_at', [$filter->dateFrom, $filter->dateTo])
            ->when($filter->branchId, fn ($q) => $q->where('branch_id', $filter->branchId))
            ->when($filter->accountId, fn ($q) => $q->where('ledger_account_id', $filter->accountId))
            ->when($filter->direction, fn ($q) => $q->where('direction', $filter->direction));
    }

    private function salesPayments(ReportFilter $filter): float
    {
        return (float) DB::table('sale_payments')
            ->join('sales', 'sales.id', '=', 'sale_payments.sale_id')
            ->where('sale_payments.tenant_id', \tenant_id())
            ->whereBetween('sales.sold_at', [$filter->dateFrom, $filter->dateTo])
            ->when($filter->branchId, fn ($q) => $q->where('sales.branch_id', $filter->branchId))
            ->when($filter->paymentMethod, fn ($q) => $q->where('sale_payments.method', $filter->paymentMethod))
            ->sum('sale_payments.amount');
    }

    private function purchasePayments(ReportFilter $filter): float
    {
        return (float) DB::table('purchase_payments')
            ->where('tenant_id', \tenant_id())
            ->whereBetween('paid_at', [$filter->dateFrom->toDateString(), $filter->dateTo->toDateString()])
            ->when($filter->branchId, fn ($q) => $q->where('paying_branch_id', $filter->branchId))
            ->when($filter->paymentMethod, fn ($q) => $q->where('method', $filter->paymentMethod))
            ->sum('amount');
    }
}
