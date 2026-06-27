<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Catalog\Models\Product;
use App\Domain\Sales\Models\Customer;
use App\Domain\Sales\Services\SaleService;
use App\Http\Controllers\Controller;
use App\Http\Resources\Catalog\ProductResource;
use App\Http\Requests\Sales\StorePosSaleRequest;
use App\Support\Sales\InvoiceRounding;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
            'quickProducts' => [
                'popular' => $this->popularProducts(),
                'latest' => $this->latestProducts(),
                'lastSold' => $this->lastSoldProducts(),
            ],
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

    private function latestProducts(): array
    {
        $products = $this->productCardQuery()
            ->orderByDesc('id')
            ->limit(12)
            ->get();

        return $this->resolveProducts($products);
    }

    private function popularProducts(): array
    {
        $ids = DB::table('sale_lines')
            ->join('sales', 'sales.id', '=', 'sale_lines.sale_id')
            ->where('sale_lines.tenant_id', tenant_id())
            ->where('sales.tenant_id', tenant_id())
            ->where('sales.status', 'posted')
            ->where('sales.sold_at', '>=', now()->subDays(30))
            ->select('sale_lines.product_id', DB::raw('SUM(sale_lines.quantity_base) as sold_quantity'))
            ->groupBy('sale_lines.product_id')
            ->orderByDesc('sold_quantity')
            ->limit(12)
            ->pluck('product_id')
            ->filter()
            ->values();

        return $this->productsByIds($ids);
    }

    private function lastSoldProducts(): array
    {
        $ids = DB::table('sale_lines')
            ->join('sales', 'sales.id', '=', 'sale_lines.sale_id')
            ->where('sale_lines.tenant_id', tenant_id())
            ->where('sales.tenant_id', tenant_id())
            ->where('sales.status', 'posted')
            ->orderByDesc('sales.sold_at')
            ->orderByDesc('sale_lines.id')
            ->limit(50)
            ->pluck('sale_lines.product_id')
            ->filter()
            ->unique()
            ->take(12)
            ->values();

        return $this->productsByIds($ids);
    }

    /**
     * @param  Collection<int, int|string>  $ids
     */
    private function productsByIds(Collection $ids): array
    {
        if ($ids->isEmpty()) {
            return [];
        }

        $products = $this->productCardQuery()
            ->whereIn('id', $ids->all())
            ->get()
            ->sortBy(fn (Product $product) => $ids->search($product->getKey()))
            ->values();

        return $this->resolveProducts($products);
    }

    private function productCardQuery(): Builder
    {
        return Product::query()
            ->where('is_active', true)
            ->whereHas('batches', fn (Builder $query) => $this->sellableBatchScope($query))
            ->with([
                'units',
                'storageLocation',
                'batches' => fn ($query) => $this->sellableBatchScope($query)
                    ->with('storageLocation')
                    ->orderByRaw('expiry_date IS NULL')
                    ->orderBy('expiry_date')
                    ->orderBy('id'),
            ]);
    }

    private function sellableBatchScope($query)
    {
        return $query
            ->where('quantity_on_hand', '>', 0)
            ->where(function (Builder $query) {
                $query->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>=', now()->toDateString());
            });
    }

    /**
     * @param  Collection<int, Product>  $products
     */
    private function resolveProducts(Collection $products): array
    {
        return ProductResource::collection($products)->resolve();
    }
}
