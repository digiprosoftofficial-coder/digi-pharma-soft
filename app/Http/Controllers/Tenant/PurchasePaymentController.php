<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Purchasing\Models\Purchase;
use App\Domain\Purchasing\Services\PurchaseService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Purchasing\StorePurchasePaymentRequest;
use App\Support\Tenant\SupplierPaymentSettings;
use Illuminate\Http\RedirectResponse;

final class PurchasePaymentController extends Controller
{
    public function __construct(private readonly PurchaseService $purchases) {}

    public function store(StorePurchasePaymentRequest $request, int $purchase): RedirectResponse
    {
        abort_unless(SupplierPaymentSettings::userCanRecordPayment($request->user()), 403);

        $purchaseModel = Purchase::query()
            ->withoutGlobalScope('branch')
            ->whereKey($purchase)
            ->firstOrFail();

        try {
            $this->purchases->recordPayment(
                $purchaseModel,
                $request->validated('method'),
                (float) $request->validated('amount'),
                $request->validated('paid_at'),
                $request->validated('reference'),
                $request->validated('notes'),
            );
        } catch (\RuntimeException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['amount' => $e->getMessage()]);
        }

        $redirect = $request->string('redirect')->toString();

        if ($redirect === 'supplier_bill' && $purchaseModel->supplier_id) {
            return redirect()
                ->route('tenant.purchases.supplier-bills.show', $purchaseModel->supplier_id)
                ->with('success', __('purchases.payment_recorded'));
        }

        return redirect()
            ->route('tenant.purchases.show', $purchaseModel)
            ->with('success', __('purchases.payment_recorded'));
    }
}
