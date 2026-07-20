<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Catalog\Models\StorageLocation;
use App\Domain\Catalog\Repositories\ProductRepository;
use App\Domain\Purchasing\Models\Purchase;
use App\Domain\Purchasing\Models\Supplier;
use App\Domain\Purchasing\Services\PurchaseService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Purchasing\StorePurchaseRequest;
use App\Http\Resources\Catalog\ProductResource;
use App\Support\Payments\PaymentMethods;
use App\Support\Purchasing\LastPurchasePriceLookup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PurchaseController extends Controller
{
    public function __construct(
        private readonly PurchaseService $purchases,
        private readonly ProductRepository $products,
        private readonly LastPurchasePriceLookup $lastPurchase,
    ) {}

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
            ->with(['supplier', 'lines.product'])
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

    public function create(Request $request): Response
    {
        $this->authorize('create', Purchase::class);

        $prefillProducts = [];
        $ids = $this->parseProductIds($request->input('product_ids'));

        if ($ids !== []) {
            $items = $this->products->findManyForPurchase($ids);
            $this->lastPurchase->attachToProducts($items);
            $prefillProducts = ProductResource::collection($items)->resolve();
        }

        return Inertia::render('Purchases/Create', [
            'paymentMethods' => PaymentMethods::options(),
            'storageLocations' => StorageLocation::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
            'prefillProducts' => $prefillProducts,
        ]);
    }

    /**
     * @return list<int>
     */
    private function parseProductIds(mixed $raw): array
    {
        if (is_array($raw)) {
            $parts = $raw;
        } elseif (is_string($raw) && $raw !== '') {
            $parts = preg_split('/[,\s]+/', $raw) ?: [];
        } else {
            return [];
        }

        return collect($parts)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->take(50)
            ->values()
            ->all();
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

        $lines = $request->validated('lines');
        $discount = (float) $request->validated('discount', 0);
        if ($request->validated('discount_type') === 'percent') {
            $subtotal = collect($lines)->sum(fn (array $line) => (float) $line['quantity'] * (float) $line['unit_cost']);
            $discount = min($subtotal, $subtotal * min(100, $discount) / 100);
        }

        $this->purchases->recordPurchase(
            $supplier,
            $request->validated('invoice_no'),
            $request->validated('purchased_at'),
            $lines,
            (float) $request->validated('tax', 0),
            $discount,
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
