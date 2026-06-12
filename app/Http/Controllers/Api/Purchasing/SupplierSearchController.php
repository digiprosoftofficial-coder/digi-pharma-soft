<?php

namespace App\Http\Controllers\Api\Purchasing;

use App\Domain\Purchasing\Models\Supplier;
use App\Domain\Purchasing\Services\SupplierDueService;
use App\Http\Controllers\Controller;
use App\Support\Tenant\TenantFeatures;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SupplierSearchController extends Controller
{
    public function __construct(private readonly SupplierDueService $dues) {}

    public function __invoke(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('purchases.manage'), 403);

        $term = trim((string) $request->input('q'));

        if (strlen($term) < 1) {
            return response()->json(['data' => []]);
        }

        $viewAll = ($request->user()?->can('purchases.view_all_branches') ?? false)
            && TenantFeatures::supplierBranchLedgerEnabled(tenant());
        $branchId = \branch_id();

        $suppliers = Supplier::query()
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', '%'.$term.'%')
                    ->orWhere('phone', 'like', '%'.$term.'%')
                    ->orWhere('email', 'like', '%'.$term.'%');
            })
            ->orderBy('name')
            ->limit(15)
            ->get(['id', 'name', 'phone', 'email']);

        $data = $suppliers->map(function (Supplier $supplier) use ($viewAll, $branchId) {
            return [
                'id' => $supplier->getKey(),
                'name' => $supplier->name,
                'phone' => $supplier->phone,
                'email' => $supplier->email,
                'branch_due' => $this->dues->displayDue($supplier, false, $branchId),
                'total_due' => $viewAll ? $this->dues->totalDue($supplier) : null,
            ];
        });

        return response()->json(['data' => $data->values()->all()]);
    }
}
