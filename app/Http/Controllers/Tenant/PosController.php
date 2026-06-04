<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Sales\Models\Customer;
use App\Domain\Sales\Services\SaleService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\StorePosSaleRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

final class PosController extends Controller
{
    public function __construct(private readonly SaleService $sales) {}

    public function index(): Response
    {
        $this->authorize('create', \App\Domain\Sales\Models\Sale::class);

        $lastSaleId = session('last_sale_id');
        session()->forget('last_sale_id');

        return Inertia::render('Pos/Index', [
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name', 'phone', 'balance_due']),
            'lastSaleId' => $lastSaleId,
        ]);
    }

    public function store(StorePosSaleRequest $request): RedirectResponse
    {
        try {
            $sale = $this->sales->checkout(
                $request->validated('customer_id'),
                $request->validated('lines'),
                $request->validated('payments'),
                (float) $request->validated('discount_percent', 0),
                (float) $request->validated('tax', 0),
                $request->validated('coupon_code'),
            );
        } catch (RuntimeException $e) {
            return redirect()
                ->route('tenant.pos.index')
                ->withErrors(['checkout' => $e->getMessage()]);
        }

        return redirect()
            ->route('tenant.pos.index')
            ->with('success', __('Sale completed.'))
            ->with('last_sale_id', $sale->getKey());
    }
}
