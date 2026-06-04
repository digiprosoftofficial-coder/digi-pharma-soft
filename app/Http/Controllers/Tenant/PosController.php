<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Sales\Models\Customer;
use App\Domain\Sales\Services\SaleService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\StorePosSaleRequest;
use App\Support\Sales\InvoiceRounding;
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
            'lastSaleId' => $lastSaleId,
            'roundingMode' => InvoiceRounding::resolve(tenant()),
        ]);
    }

    public function store(StorePosSaleRequest $request): RedirectResponse
    {
        $customerId = $request->validated('customer_id');

        // Create new customer on-the-fly if provided
        $newCustomerData = $request->validated('new_customer');
        if ($newCustomerData && ! $customerId) {
            $customer = Customer::query()->create([
                'name' => $newCustomerData['name'],
                'phone' => $newCustomerData['phone'] ?? null,
                'balance_due' => 0,
            ]);
            $customerId = $customer->getKey();
        }

        try {
            $sale = $this->sales->checkout(
                $customerId,
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
