<?php

namespace App\Services\Reports;

use App\Domain\Sales\Models\Customer;
use App\Domain\Sales\Models\Sale;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class CustomerReportService
{
    public function summary(ReportFilter $filter): array
    {
        $rows = $this->customerRows($filter);

        return [
            'customerCount' => $rows->count(),
            'salesTotal' => (float) $rows->sum('sales_total'),
            'paid' => (float) $rows->sum('paid_total'),
            'due' => (float) $rows->sum('due_total'),
            'invoiceCount' => (int) $rows->sum('invoice_count'),
        ];
    }

    public function rows(ReportFilter $filter, int $perPage = 25): LengthAwarePaginator
    {
        $rows = $this->customerRows($filter)->values();
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
        return $this->customerRows($filter)
            ->map(fn (array $row) => [
                $row['customer_name'],
                $row['phone'],
                $row['invoice_count'],
                $row['sales_total'],
                $row['paid_total'],
                $row['due_total'],
                $row['loyalty_points'],
            ])
            ->values()
            ->all();
    }

    private function customerRows(ReportFilter $filter): Collection
    {
        $customers = Customer::query()
            ->when($filter->customerId, fn ($q) => $q->whereKey($filter->customerId))
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'balance_due', 'loyalty_points']);

        return $customers->map(function (Customer $customer) use ($filter) {
            $sales = Sale::query()
                ->withoutGlobalScope('branch')
                ->where('customer_id', $customer->getKey())
                ->where('status', 'posted')
                ->whereBetween('sold_at', [$filter->dateFrom, $filter->dateTo]);

            if ($filter->branchId !== null) {
                $sales->where('branch_id', $filter->branchId);
            }
            if ($filter->paymentStatus === 'paid') {
                $sales->where('due', '<=', 0);
            } elseif ($filter->paymentStatus === 'due') {
                $sales->where('due', '>', 0);
            } elseif ($filter->paymentStatus === 'partial') {
                $sales->where('paid', '>', 0)->where('due', '>', 0);
            }

            return [
                'customer_id' => $customer->getKey(),
                'customer_name' => $customer->name,
                'phone' => $customer->phone,
                'invoice_count' => (clone $sales)->count(),
                'sales_total' => (float) (clone $sales)->sum('total'),
                'paid_total' => (float) (clone $sales)->sum('paid'),
                'due_total' => $filter->branchId === null
                    ? (float) $customer->balance_due
                    : (float) (clone $sales)->sum('due'),
                'loyalty_points' => (float) $customer->loyalty_points,
            ];
        })->filter(function (array $row) use ($filter) {
            if ($filter->dueStatus === 'has_due') {
                return $row['due_total'] > 0;
            }
            if ($filter->dueStatus === 'clear') {
                return $row['due_total'] <= 0 && $row['invoice_count'] > 0;
            }

            return $row['invoice_count'] > 0 || $row['due_total'] > 0;
        })->sortByDesc('sales_total')->values();
    }
}
