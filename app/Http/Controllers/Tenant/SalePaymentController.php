<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Sales\Models\Sale;
use App\Domain\Sales\Services\SaleService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\StoreSalePaymentRequest;
use Illuminate\Http\RedirectResponse;

final class SalePaymentController extends Controller
{
    public function __construct(private readonly SaleService $sales) {}

    public function store(StoreSalePaymentRequest $request, int $sale): RedirectResponse
    {
        $saleModel = Sale::query()
            ->withoutGlobalScope('branch')
            ->whereKey($sale)
            ->firstOrFail();

        try {
            $this->sales->recordPayment(
                $saleModel,
                $request->validated('method'),
                (float) $request->validated('amount'),
            );
        } catch (\RuntimeException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['amount' => $e->getMessage()]);
        }

        if ($request->string('redirect')->toString() === 'customer_bill' && $saleModel->customer_id) {
            return redirect()
                ->route('tenant.sales.customer-bills.show', $saleModel->customer_id)
                ->with('success', __('sales.payment_recorded'));
        }

        return redirect()
            ->route('tenant.sales.index')
            ->with('success', __('sales.payment_recorded'));
    }
}
