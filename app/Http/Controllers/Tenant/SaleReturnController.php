<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Sales\Models\Sale;
use App\Domain\Sales\Models\SaleReturn;
use App\Domain\Sales\Services\ReturnService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class SaleReturnController extends Controller
{
    public function __construct(private readonly ReturnService $returns) {}

    public function index(): Response
    {
        $this->authorize('viewAny', SaleReturn::class);

        $returns = SaleReturn::query()
            ->with('sale')
            ->orderByDesc('returned_at')
            ->paginate(20);

        return Inertia::render('Sales/Returns/Index', [
            'saleReturns' => $returns,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', SaleReturn::class);

        return Inertia::render('Sales/Returns/Create', [
            'sales' => Sale::query()->orderByDesc('sold_at')->limit(100)->get(['id', 'invoice_no', 'sold_at', 'total']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', SaleReturn::class);

        $tid = tenant_id();
        $validated = $request->validate([
            'sale_id' => ['nullable', 'integer', Rule::exists('sales', 'id')->where('tenant_id', $tid)],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_batch_id' => ['required', 'integer', Rule::exists('product_batches', 'id')->where('tenant_id', $tid)],
            'lines.*.quantity' => ['required', 'integer', 'min:1'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $this->returns->recordReturn(
            $validated['sale_id'] ?? null,
            $validated['lines'],
            $validated['notes'] ?? null,
        );

        return redirect()->route('tenant.sales.returns.index')->with('success', __('Return recorded.'));
    }
}
