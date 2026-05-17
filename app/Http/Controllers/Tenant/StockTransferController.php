<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Catalog\Models\Product;
use App\Domain\Inventory\Models\StockTransfer;
use App\Domain\Inventory\Services\StockTransferService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class StockTransferController extends Controller
{
    public function __construct(private readonly StockTransferService $transfers) {}

    public function index(): Response
    {
        $this->authorize('viewAny', StockTransfer::class);

        $transfers = StockTransfer::query()
            ->orderByDesc('transferred_at')
            ->paginate(20);

        return Inertia::render('StockTransfers/Index', [
            'transfers' => $transfers,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', StockTransfer::class);

        $products = Product::query()
            ->with(['batches' => fn ($q) => $q->where('quantity_on_hand', '>', 0)])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);

        return Inertia::render('StockTransfers/Create', [
            'products' => $products,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', StockTransfer::class);

        $tid = tenant_id();
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.from_batch_id' => ['required', 'integer', Rule::exists('product_batches', 'id')->where('tenant_id', $tid)],
            'lines.*.to_batch_id' => ['required', 'integer', Rule::exists('product_batches', 'id')->where('tenant_id', $tid)],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
        ]);

        $this->transfers->recordTransfer($validated['lines'], $validated['notes'] ?? null);

        return redirect()->route('tenant.stock-transfers.index')->with('success', __('Transfer completed.'));
    }
}
