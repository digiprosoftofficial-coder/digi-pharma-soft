<?php

namespace App\Services\Reports;

use App\Domain\Purchasing\Models\Supplier;
use App\Domain\Purchasing\Services\SupplierDueService as SupplierLedger;
use App\Domain\Sales\Models\Customer;
use App\Domain\Sales\Models\Sale;
use Illuminate\Support\Collection;

final readonly class DueReportService
{
    public function __construct(private SupplierLedger $supplierLedger) {}

    public function summary(ReportFilter $filter): array
    {
        return [
            'customerDue' => $this->customerRows($filter)->sum('due'),
            'supplierDue' => $this->supplierRows($filter)->sum('due'),
            'customerCount' => $this->customerRows($filter)->count(),
            'supplierCount' => $this->supplierRows($filter)->count(),
        ];
    }

    public function customerRows(ReportFilter $filter, ?int $limit = null): Collection
    {
        if ($filter->branchId !== null) {
            $dueByCustomer = Sale::query()
                ->withoutGlobalScope('branch')
                ->selectRaw('customer_id, SUM(due) as due')
                ->whereNotNull('customer_id')
                ->where('status', 'posted')
                ->where('branch_id', $filter->branchId)
                ->where('due', '>', 0)
                ->groupBy('customer_id')
                ->orderByDesc('due');

            if ($limit !== null) {
                $dueByCustomer->limit($limit);
            }

            $rows = $dueByCustomer->get();
            $customers = Customer::query()
                ->whereIn('id', $rows->pluck('customer_id'))
                ->get(['id', 'name', 'phone'])
                ->keyBy('id');

            return $rows->map(fn ($row) => [
                'id' => (int) $row->customer_id,
                'name' => $customers[$row->customer_id]->name ?? 'Unknown customer',
                'phone' => $customers[$row->customer_id]->phone ?? null,
                'due' => (float) $row->due,
                'scope' => $filter->branchLabel,
            ]);
        }

        $query = Customer::query()
            ->where('balance_due', '>', 0)
            ->orderByDesc('balance_due');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query
            ->get(['id', 'name', 'phone', 'balance_due'])
            ->map(fn (Customer $customer) => [
                'id' => $customer->getKey(),
                'name' => $customer->name,
                'phone' => $customer->phone,
                'due' => (float) $customer->balance_due,
                'scope' => 'Tenant-wide',
            ]);
    }

    public function supplierRows(ReportFilter $filter, ?int $limit = null): Collection
    {
        $query = $this->supplierLedger->suppliersWithOpenDueQuery($filter->branchId);

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query
            ->get(['id', 'name', 'phone', 'balance_due'])
            ->map(fn (Supplier $supplier) => [
                'id' => $supplier->getKey(),
                'name' => $supplier->name,
                'phone' => $supplier->phone,
                'due' => $filter->branchId === null
                    ? $this->supplierLedger->totalDue($supplier)
                    : $this->supplierLedger->branchDue($supplier, $filter->branchId),
                'scope' => $filter->branchLabel,
            ])
            ->filter(fn (array $row) => $row['due'] > 0)
            ->values();
    }

    public function exportRows(ReportFilter $filter): array
    {
        $customerRows = $this->customerRows($filter)
            ->map(fn (array $row) => ['Customer', $row['name'], $row['phone'], $row['scope'], $row['due']]);

        $supplierRows = $this->supplierRows($filter)
            ->map(fn (array $row) => ['Supplier', $row['name'], $row['phone'], $row['scope'], $row['due']]);

        return $customerRows->merge($supplierRows)->values()->all();
    }
}
