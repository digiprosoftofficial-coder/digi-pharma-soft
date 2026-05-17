<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Sales\Services\SaleService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\StorePosSaleRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class PosController extends Controller
{
    public function __construct(private readonly SaleService $sales) {}

    public function index(): Response
    {
        $this->authorize('create', \App\Domain\Sales\Models\Sale::class);

        return Inertia::render('Pos/Index');
    }

    public function store(StorePosSaleRequest $request): RedirectResponse
    {
        $this->sales->checkout(
            $request->validated('customer_id'),
            $request->validated('lines'),
            $request->validated('payments'),
            (float) $request->validated('discount', 0),
            (float) $request->validated('tax', 0),
            $request->validated('coupon_code'),
        );

        return redirect()->route('tenant.pos.index')->with('success', __('Sale completed.'));
    }
}
