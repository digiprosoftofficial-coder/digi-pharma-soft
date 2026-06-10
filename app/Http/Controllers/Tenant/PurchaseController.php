<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Catalog\Models\StorageLocation;
use App\Domain\Purchasing\Models\Purchase;
use App\Domain\Purchasing\Models\Supplier;
use App\Domain\Purchasing\Services\PurchaseService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Purchasing\StorePurchaseRequest;
use App\Support\Payments\PaymentMethods;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PurchaseController extends Controller
{
    public function __construct(private readonly PurchaseService $purchases) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Purchase::class);

        $filters = [
            'q' => $request->string('q')->trim()->toString(),
            'supplier_id' => $request->string('supplier_id')->toString(),
            'date_from' => $request->string('date_from')->toString(),
            'date_to' => $request->string('date_to')->toString(),
        ];

        $query = Purchase::query()
            ->with('supplier')
            ->orderByDesc('purchased_at');

        if ($filters['supplier_id'] !== '') {
            $query->where('supplier_id', (int) $filters['supplier_id']);
        }

        if ($filters['date_from'] !== '') {
            $query->whereDate('purchased_at', '>=', $filters['date_from']);
        }

        if ($filters['date_to'] !== '') {
            $query->whereDate('purchased_at', '<=', $filters['date_to']);
        }

        if ($filters['q'] !== '') {
            $term = $filters['q'];
            $query->where(function ($w) use ($term) {
                $w->where('invoice_no', 'like', '%'.$term.'%')
                    ->orWhereHas('lines.product', function ($productQuery) use ($term) {
                        $productQuery->where('name', 'like', '%'.$term.'%')
                            ->orWhere('generic_name', 'like', '%'.$term.'%')
                            ->orWhere('sku', 'like', '%'.$term.'%');
                    });
            });
        }

        return Inertia::render('Purchases/Index', [
            'purchases' => $query->paginate(20)->withQueryString(),
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name']),
            'filters' => $filters,
        ]);
    }

    public function show(Purchase $purchase): Response
    {
        $this->authorize('view', $purchase);

        $purchase->load([
            'supplier',
            'lines' => fn ($q) => $q->with('product')->orderBy('id'),
            'payments' => fn ($q) => $q->orderByDesc('paid_at')->orderByDesc('id'),
        ]);

        $canManage = auth()->user()?->can('purchases.manage') ?? false;

        return Inertia::render('Purchases/Show', [
            'purchase' => $purchase,
            'paymentMethods' => PaymentMethods::options(),
            'canManage' => $canManage,
            'canVoid' => $canManage && $purchase->status !== 'voided',
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Purchase::class);

        return Inertia::render('Purchases/Create', [
            'paymentMethods' => PaymentMethods::options(),
            'storageLocations' => StorageLocation::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
        ]);
    }

    public function store(StorePurchaseRequest $request): RedirectResponse
    {
        $supplierId = $request->validated('supplier_id');
        $newSupplier = $request->validated('new_supplier');

        if ($newSupplier && ! $supplierId) {
            $supplier = Supplier::query()->create([
                'name' => $newSupplier['name'],
                'phone' => $newSupplier['phone'] ?? null,
                'email' => $newSupplier['email'] ?? null,
                'balance_due' => 0,
            ]);
        } else {
            $supplier = Supplier::query()->whereKey($supplierId)->firstOrFail();
        }

        $this->purchases->recordPurchase(
            $supplier,
            $request->validated('invoice_no'),
            $request->validated('purchased_at'),
            $request->validated('lines'),
            (float) $request->validated('tax', 0),
            (float) $request->validated('discount', 0),
            (float) $request->validated('paid', 0),
            $request->validated('payment_method'),
            $request->validated('notes'),
        );

        return redirect()->route('tenant.purchases.index')->with('success', __('Purchase recorded.'));
    }

    public function void(Purchase $purchase): RedirectResponse
    {
        $this->authorize('void', $purchase);

        try {
            $this->purchases->voidPurchase($purchase);
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('tenant.purchases.show', $purchase)
                ->withErrors(['void' => $e->getMessage()]);
        }

        return redirect()
            ->route('tenant.purchases.show', $purchase)
            ->with('success', __('purchases.purchase_voided'));
    }
}
