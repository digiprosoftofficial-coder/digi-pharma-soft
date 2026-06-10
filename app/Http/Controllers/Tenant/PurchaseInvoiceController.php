<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Purchasing\Models\Purchase;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

final class PurchaseInvoiceController extends Controller
{
    public function print(Purchase $purchase): View
    {
        $this->authorize('view', $purchase);

        $purchase->load([
            'supplier',
            'lines' => fn ($q) => $q->with('product')->orderBy('id'),
            'payments' => fn ($q) => $q->orderBy('paid_at'),
        ]);

        return view('purchases.invoice-print', [
            'purchase' => $purchase,
            'storeName' => tenant()?->name ?? config('app.name'),
        ]);
    }
}
