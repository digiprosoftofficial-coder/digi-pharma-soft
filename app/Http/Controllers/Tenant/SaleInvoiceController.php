<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Sales\Models\Sale;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

final class SaleInvoiceController extends Controller
{
    public function print(Sale $sale): View
    {
        $this->authorize('view', $sale);

        $sale->load(['customer', 'lines.product', 'lines.batch', 'payments']);

        return view('sales.invoice-print', [
            'sale' => $sale,
            'storeName' => tenant()?->name ?? config('app.name'),
        ]);
    }
}
