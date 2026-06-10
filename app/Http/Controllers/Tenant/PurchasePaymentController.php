<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Purchasing\Models\Purchase;
use App\Domain\Purchasing\Services\PurchaseService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Purchasing\StorePurchasePaymentRequest;
use Illuminate\Http\RedirectResponse;

final class PurchasePaymentController extends Controller
{
    public function __construct(private readonly PurchaseService $purchases) {}

    public function store(StorePurchasePaymentRequest $request, Purchase $purchase): RedirectResponse
    {
        $this->purchases->recordPayment(
            $purchase,
            $request->validated('method'),
            (float) $request->validated('amount'),
            $request->validated('paid_at'),
            $request->validated('reference'),
            $request->validated('notes'),
        );

        $redirect = $request->string('redirect')->toString();

        if ($redirect === 'supplier_bill' && $purchase->supplier_id) {
            return redirect()
                ->route('tenant.purchases.supplier-bills.show', $purchase->supplier_id)
                ->with('success', __('purchases.payment_recorded'));
        }

        return redirect()
            ->route('tenant.purchases.show', $purchase)
            ->with('success', __('purchases.payment_recorded'));
    }
}
