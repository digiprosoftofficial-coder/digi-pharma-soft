<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Catalog\Models\StorageLocation;
use App\Domain\Purchasing\Models\Purchase;
use App\Domain\Purchasing\Models\Supplier;
use App\Domain\Purchasing\Services\PurchaseService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Purchasing\StorePurchaseRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class PurchaseController extends Controller
{
    public function __construct(private readonly PurchaseService $purchases) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Purchase::class);

        $purchases = Purchase::query()
            ->with('supplier')
            ->orderByDesc('purchased_at')
            ->paginate(20);

        return Inertia::render('Purchases/Index', [
            'purchases' => $purchases,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Purchase::class);

        return Inertia::render('Purchases/Create', [
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name']),
            'storageLocations' => StorageLocation::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
        ]);
    }

    public function store(StorePurchaseRequest $request): RedirectResponse
    {
        $supplier = Supplier::query()->whereKey($request->validated('supplier_id'))->firstOrFail();

        $this->purchases->recordPurchase(
            $supplier,
            $request->validated('invoice_no'),
            $request->validated('purchased_at'),
            $request->validated('lines'),
            (float) $request->validated('tax', 0),
            (float) $request->validated('discount', 0),
            (float) $request->validated('paid', 0),
        );

        return redirect()->route('tenant.purchases.index')->with('success', __('Purchase recorded.'));
    }
}
