<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Sales\Models\Sale;
use App\Domain\Sales\Services\SaleService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class SaleController extends Controller
{
    public function __construct(private readonly SaleService $sales) {}

    public function index(): Response
    {
        abort_unless(auth()->user()?->can('sales.view'), 403);

        $sales = Sale::query()
            ->with([
                'customer',
                'lines.product',
                'lines.batch',
            ])
            ->orderByDesc('sold_at')
            ->paginate(20);

        return Inertia::render('Sales/Index', [
            'sales' => $sales,
            'canVoid' => auth()->user()?->can('pos.access') ?? false,
        ]);
    }

    public function void(Sale $sale): RedirectResponse
    {
        $this->authorize('void', $sale);

        $this->sales->voidSale($sale);

        return redirect()
            ->route('tenant.sales.index')
            ->with('success', __('sales.invoice_voided'));
    }
}
