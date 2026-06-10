<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Purchasing\Models\Purchase;
use App\Domain\Purchasing\Models\PurchasePayment;
use App\Domain\Purchasing\Models\Supplier;
use App\Support\Payments\PaymentMethods;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

final class SupplierBillsController extends Controller
{
    public function index(): Response
    {
        abort_unless(auth()->user()?->can('purchases.view'), 403);

        $suppliers = Supplier::query()
            ->whereHas('purchases', fn ($q) => $q->where('due', '>', 0))
            ->withSum(['purchases as purchases_sum_due' => fn ($q) => $q->where('due', '>', 0)], 'due')
            ->orderByDesc('purchases_sum_due')
            ->paginate(20);

        return Inertia::render('Purchases/SupplierBills', [
            'suppliers' => $suppliers,
        ]);
    }

    public function show(Supplier $supplier): Response
    {
        abort_unless(auth()->user()?->can('purchases.view'), 403);
        abort_unless((int) auth()->user()?->tenant_id === (int) $supplier->tenant_id, 403);

        $openPurchases = Purchase::query()
            ->where('supplier_id', $supplier->getKey())
            ->where('due', '>', 0)
            ->orderBy('purchased_at')
            ->get(['id', 'invoice_no', 'purchased_at', 'total', 'paid', 'due']);

        $paymentHistory = PurchasePayment::query()
            ->whereHas('purchase', fn ($q) => $q->where('supplier_id', $supplier->getKey()))
            ->with(['purchase:id,invoice_no,supplier_id'])
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return Inertia::render('Purchases/SupplierBillShow', [
            'supplier' => $supplier->only(['id', 'name', 'phone', 'email', 'balance_due']),
            'openPurchases' => $openPurchases,
            'paymentHistory' => $paymentHistory,
            'paymentMethods' => PaymentMethods::options(),
            'canManage' => auth()->user()?->can('purchases.manage') ?? false,
        ]);
    }
}
