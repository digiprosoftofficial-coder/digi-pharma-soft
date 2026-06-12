<?php

namespace App\Domain\Purchasing\Services;

use App\Domain\Purchasing\Models\Purchase;
use App\Domain\Purchasing\Models\PurchaseReturn;
use App\Domain\Purchasing\Models\Supplier;
use App\Domain\Tenant\Models\Branch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class SupplierDueService
{
    public function branchDue(Supplier $supplier, int $branchId): float
    {
        $purchaseDue = (float) Purchase::query()
            ->withoutGlobalScope('branch')
            ->where('supplier_id', $supplier->getKey())
            ->where('branch_id', $branchId)
            ->where('status', 'posted')
            ->sum('due');

        $returnCredit = (float) PurchaseReturn::query()
            ->withoutGlobalScope('branch')
            ->where('supplier_id', $supplier->getKey())
            ->where('branch_id', $branchId)
            ->where('status', 'posted')
            ->sum('total_credit');

        return max(0, $purchaseDue - $returnCredit);
    }

    public function totalDue(Supplier $supplier): float
    {
        $purchaseDue = (float) Purchase::query()
            ->withoutGlobalScope('branch')
            ->where('supplier_id', $supplier->getKey())
            ->where('status', 'posted')
            ->sum('due');

        $returnCredit = (float) PurchaseReturn::query()
            ->withoutGlobalScope('branch')
            ->where('supplier_id', $supplier->getKey())
            ->where('status', 'posted')
            ->sum('total_credit');

        return max(0, $purchaseDue - $returnCredit);
    }

    /**
     * @return Collection<int, array{branch_id:int, branch_name:string, branch_code:string, due:float}>
     */
    public function breakdownByBranch(Supplier $supplier): Collection
    {
        $purchaseRows = Purchase::query()
            ->withoutGlobalScope('branch')
            ->select('branch_id', DB::raw('SUM(due) as purchase_due'))
            ->where('supplier_id', $supplier->getKey())
            ->where('status', 'posted')
            ->where('due', '>', 0)
            ->groupBy('branch_id')
            ->get()
            ->keyBy('branch_id');

        $returnRows = PurchaseReturn::query()
            ->withoutGlobalScope('branch')
            ->select('branch_id', DB::raw('SUM(total_credit) as return_credit'))
            ->where('supplier_id', $supplier->getKey())
            ->where('status', 'posted')
            ->groupBy('branch_id')
            ->get()
            ->keyBy('branch_id');

        $branchIds = $purchaseRows->keys()->merge($returnRows->keys())->unique()->filter();

        $branches = Branch::query()
            ->withoutGlobalScopes()
            ->whereIn('id', $branchIds)
            ->get(['id', 'name', 'code'])
            ->keyBy('id');

        return $branchIds->map(function ($branchId) use ($purchaseRows, $returnRows, $branches) {
            $purchaseDue = (float) ($purchaseRows[$branchId]->purchase_due ?? 0);
            $returnCredit = (float) ($returnRows[$branchId]->return_credit ?? 0);
            $branch = $branches[$branchId] ?? null;

            return [
                'branch_id' => (int) $branchId,
                'branch_name' => $branch?->name ?? '—',
                'branch_code' => $branch?->code ?? '—',
                'due' => max(0, $purchaseDue - $returnCredit),
            ];
        })->filter(fn (array $row) => $row['due'] > 0)->values();
    }

    /**
     * Suppliers that have open payables, optionally scoped to one invoice branch.
     */
    public function suppliersWithOpenDueQuery(?int $branchId = null): Builder
    {
        $query = Supplier::query()
            ->whereExists(function ($q) use ($branchId) {
                $q->select(DB::raw(1))
                    ->from('purchases')
                    ->whereColumn('purchases.supplier_id', 'suppliers.id')
                    ->where('purchases.status', 'posted')
                    ->where('purchases.due', '>', 0);

                if ($branchId !== null) {
                    $q->where('purchases.branch_id', $branchId);
                }
            })
            ->orderBy('name');

        if ($branchId !== null) {
            $query->withSum(
                ['purchases as purchases_sum_due' => fn ($q) => $q->where('status', 'posted')->where('due', '>', 0)->where('branch_id', $branchId)],
                'due',
            );
        } else {
            $query->withSum(
                ['purchases as purchases_sum_due' => fn ($q) => $q->where('status', 'posted')->where('due', '>', 0)],
                'due',
            );
        }

        return $query;
    }

    public function displayDue(Supplier $supplier, bool $viewAllBranches, ?int $activeBranchId): float
    {
        if ($viewAllBranches) {
            return $this->totalDue($supplier);
        }

        if ($activeBranchId === null) {
            return $this->totalDue($supplier);
        }

        return $this->branchDue($supplier, $activeBranchId);
    }
}
