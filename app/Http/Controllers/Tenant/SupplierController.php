<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Purchasing\Models\Supplier;
use App\Domain\Purchasing\Services\SupplierDueService;
use App\Http\Controllers\Controller;
use App\Support\Tenant\TenantFeatures;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class SupplierController extends Controller
{
    public function __construct(private readonly SupplierDueService $dues)
    {
        $this->authorizeResource(Supplier::class, 'supplier');
    }

    public function index(): Response
    {
        $viewAll = (auth()->user()?->can('purchases.view_all_branches') ?? false)
            && TenantFeatures::supplierBranchLedgerEnabled(tenant());
        $branchId = \branch_id();

        $suppliers = Supplier::query()
            ->withCount(['purchases', 'purchaseReturns'])
            ->orderBy('name')
            ->paginate(20);

        $suppliers->getCollection()->transform(function (Supplier $supplier) use ($viewAll, $branchId) {
            $supplier->setAttribute('open_due', $this->dues->displayDue($supplier, $viewAll, $branchId));

            return $supplier;
        });

        return Inertia::render('Suppliers/Index', [
            'suppliers' => $suppliers,
            'branchLedgerEnabled' => TenantFeatures::supplierBranchLedgerEnabled(tenant()),
            'viewAllBranches' => $viewAll,
        ]);
    }

    public function show(Supplier $supplier): Response
    {
        $viewAll = (auth()->user()?->can('purchases.view_all_branches') ?? false)
            && TenantFeatures::supplierBranchLedgerEnabled(tenant());

        $supplier->loadCount(['purchases', 'purchaseReturns']);

        return Inertia::render('Suppliers/Show', [
            'supplier' => $supplier->only([
                'id', 'name', 'phone', 'email', 'purchases_count', 'purchase_returns_count',
            ]),
            'totalDue' => $this->dues->totalDue($supplier),
            'branchBreakdown' => TenantFeatures::supplierBranchLedgerEnabled(tenant())
                ? $this->dues->breakdownByBranch($supplier)
                : [],
            'branchLedgerEnabled' => TenantFeatures::supplierBranchLedgerEnabled(tenant()),
            'viewAllBranches' => $viewAll,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Suppliers/Form', [
            'supplier' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        Supplier::query()->create($validated);

        return redirect()->route('tenant.suppliers.index')->with('success', __('Supplier created.'));
    }

    public function edit(Supplier $supplier): Response
    {
        $supplier->loadCount(['purchases', 'purchaseReturns']);

        return Inertia::render('Suppliers/Form', [
            'supplier' => $supplier,
        ]);
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $supplier->update($validated);

        return redirect()->route('tenant.suppliers.index')->with('success', __('Supplier updated.'));
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        if ($supplier->purchases()->exists() || $supplier->purchaseReturns()->exists()) {
            throw ValidationException::withMessages([
                'supplier' => [__('suppliers.cannot_delete_has_purchases')],
            ]);
        }

        $supplier->delete();

        return redirect()->route('tenant.suppliers.index')->with('success', __('suppliers.removed'));
    }
}
