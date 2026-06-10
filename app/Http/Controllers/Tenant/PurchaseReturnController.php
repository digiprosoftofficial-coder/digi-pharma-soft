<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Purchasing\Models\Purchase;
use App\Domain\Purchasing\Models\PurchaseReturn;
use App\Domain\Purchasing\Models\Supplier;
use App\Domain\Purchasing\Services\PurchaseReturnService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

final class PurchaseReturnController extends Controller
{
    public function __construct(private readonly PurchaseReturnService $returns) {}

    public function index(): Response
    {
        $this->authorize('viewAny', PurchaseReturn::class);

        return Inertia::render('Purchases/Returns/Index', [
            'returns' => PurchaseReturn::query()
                ->with(['supplier', 'purchase'])
                ->orderByDesc('returned_at')
                ->paginate(20),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', PurchaseReturn::class);

        return Inertia::render('Purchases/Returns/Create', [
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name']),
            'purchases' => Purchase::query()
                ->where('status', 'posted')
                ->orderByDesc('purchased_at')
                ->limit(100)
                ->get(['id', 'invoice_no', 'purchased_at', 'supplier_id']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', PurchaseReturn::class);

        $tid = tenant_id();
        $validated = $request->validate([
            'supplier_id' => ['required', 'integer', Rule::exists('suppliers', 'id')->where('tenant_id', $tid)],
            'purchase_id' => ['nullable', 'integer', Rule::exists('purchases', 'id')->where('tenant_id', $tid)],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_batch_id' => ['required', 'integer', Rule::exists('product_batches', 'id')->where('tenant_id', $tid)],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ]);

        $supplier = Supplier::query()->whereKey($validated['supplier_id'])->firstOrFail();

        try {
            $this->returns->recordReturn(
                $supplier,
                $validated['lines'],
                $validated['purchase_id'] ?? null,
                $validated['notes'] ?? null,
            );
        } catch (RuntimeException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['lines' => $e->getMessage()]);
        }

        return redirect()
            ->route('tenant.purchases.returns.index')
            ->with('success', __('purchases.return_recorded'));
    }
}
