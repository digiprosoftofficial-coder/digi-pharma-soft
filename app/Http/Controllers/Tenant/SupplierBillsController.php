<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Purchasing\Models\Purchase;
use App\Domain\Purchasing\Models\PurchasePayment;
use App\Domain\Purchasing\Models\Supplier;
use App\Domain\Purchasing\Services\SupplierDueService;
use App\Domain\Tenant\Models\Branch;
use App\Http\Controllers\Controller;
use App\Support\Payments\PaymentMethods;
use App\Support\Tenant\SupplierPaymentSettings;
use App\Support\Tenant\TenantFeatures;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class SupplierBillsController extends Controller
{
    public function __construct(private readonly SupplierDueService $dues) {}

    public function index(Request $request): Response
    {
        abort_unless(auth()->user()?->can('purchases.view'), 403);

        $viewAll = $this->viewAllBranches();
        $branchId = $this->resolveBranchFilter($request, $viewAll);

        $suppliers = $this->dues
            ->suppliersWithOpenDueQuery($branchId)
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Purchases/SupplierBills', [
            'suppliers' => $suppliers,
            'branchLedgerEnabled' => TenantFeatures::supplierBranchLedgerEnabled(tenant()),
            'viewAllBranches' => $viewAll,
            'branches' => $viewAll
                ? Branch::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code'])
                : [],
            'branchFilter' => $branchId,
        ]);
    }

    public function show(Request $request, Supplier $supplier): Response
    {
        abort_unless(auth()->user()?->can('purchases.view'), 403);
        abort_unless((int) auth()->user()?->tenant_id === (int) $supplier->tenant_id, 403);

        $viewAll = $this->viewAllBranches();
        $branchId = $this->resolveBranchFilter($request, $viewAll);
        $branchLedger = TenantFeatures::supplierBranchLedgerEnabled(tenant());

        $purchaseQuery = Purchase::query()
            ->when($viewAll, fn ($q) => $q->withoutGlobalScope('branch'))
            ->where('supplier_id', $supplier->getKey())
            ->where('due', '>', 0)
            ->where('status', 'posted')
            ->with('branch:id,name,code');

        if ($branchId !== null) {
            $purchaseQuery->where('branch_id', $branchId);
        }

        $openPurchases = $purchaseQuery
            ->orderBy('purchased_at')
            ->get(['id', 'invoice_no', 'purchased_at', 'total', 'paid', 'due', 'branch_id']);

        $paymentHistory = PurchasePayment::query()
            ->whereHas('purchase', function ($q) use ($supplier, $branchId, $viewAll) {
                $q->where('supplier_id', $supplier->getKey());
                if ($branchId !== null) {
                    $q->where('branch_id', $branchId);
                }
                if ($viewAll) {
                    $q->withoutGlobalScope('branch');
                }
            })
            ->with(['purchase:id,invoice_no,supplier_id,branch_id', 'payingBranch:id,name,code'])
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $due = $this->dues->displayDue($supplier, $viewAll && $branchId === null, $branchId ?? \branch_id());

        return Inertia::render('Purchases/SupplierBillShow', [
            'supplier' => [
                'id' => $supplier->getKey(),
                'name' => $supplier->name,
                'phone' => $supplier->phone,
                'email' => $supplier->email,
                'open_due' => $due,
            ],
            'branchBreakdown' => $branchLedger && $viewAll ? $this->dues->breakdownByBranch($supplier) : [],
            'openPurchases' => $openPurchases,
            'paymentHistory' => $paymentHistory,
            'paymentMethods' => PaymentMethods::options(),
            'canManage' => SupplierPaymentSettings::userCanRecordPayment(auth()->user()),
            'branchLedgerEnabled' => $branchLedger,
            'viewAllBranches' => $viewAll,
            'branches' => $viewAll
                ? Branch::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code'])
                : [],
            'branchFilter' => $branchId,
            'crossBranchEnabled' => SupplierPaymentSettings::crossBranchEnabled(tenant()),
        ]);
    }

    private function viewAllBranches(): bool
    {
        return (auth()->user()?->can('purchases.view_all_branches') ?? false)
            && TenantFeatures::supplierBranchLedgerEnabled(tenant());
    }

    private function resolveBranchFilter(Request $request, bool $viewAll): ?int
    {
        if ($viewAll) {
            $filter = $request->integer('branch_id');

            return $filter > 0 ? $filter : null;
        }

        return \branch_id();
    }
}
