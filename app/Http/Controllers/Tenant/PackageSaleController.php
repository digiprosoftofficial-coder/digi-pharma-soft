<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Sales\Models\Customer;
use App\Domain\Sales\Models\PackageTemplate;
use App\Domain\Sales\Services\SaleService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\StorePosSaleRequest;
use App\Http\Resources\Catalog\ProductResource;
use App\Support\Tenant\TenantFeatures;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use RuntimeException;
use Inertia\Inertia;
use Inertia\Response;

final class PackageSaleController extends Controller
{
    public function __construct(private readonly SaleService $sales) {}

    public function index(): Response
    {
        $this->authorize('create', \App\Domain\Sales\Models\Sale::class);
        abort_unless(TenantFeatures::packageSalesEnabled(tenant()), 403);

        $lastSaleId = session('last_sale_id');
        session()->forget('last_sale_id');

        return Inertia::render('Sales/PackageSale', [
            'lastSaleId' => $lastSaleId,
            'packageTemplates' => $this->activeTemplates(),
        ]);
    }

    public function store(StorePosSaleRequest $request): RedirectResponse
    {
        abort_unless(TenantFeatures::packageSalesEnabled(tenant()), 403);

        $customerId = $request->validated('customer_id');
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
                ->route('tenant.sales.package')
                ->withErrors(['checkout' => $e->getMessage()]);
        }

        return redirect()
            ->route('tenant.sales.package')
            ->with('success', __('Package sale completed.'))
            ->with('last_sale_id', $sale->getKey());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function activeTemplates(): array
    {
        return PackageTemplate::query()
            ->where('is_active', true)
            ->with([
                'items.product.units',
                'items.product.storageLocation',
                'items.product.batches' => fn ($query) => $this->sellableBatchScope($query)
                    ->with('storageLocation')
                    ->orderByRaw('expiry_date IS NULL')
                    ->orderBy('expiry_date')
                    ->orderBy('id'),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (PackageTemplate $template) => [
                'id' => $template->getKey(),
                'name' => $template->name,
                'description' => $template->description,
                'discount_percent' => $template->discount_percent !== null ? (float) $template->discount_percent : null,
                'fixed_price' => $template->fixed_price !== null ? (float) $template->fixed_price : null,
                'items' => $template->items->map(fn ($item) => [
                    'id' => $item->getKey(),
                    'product_id' => $item->product_id,
                    'product' => ProductResource::make($item->product)->resolve(),
                    'sell_unit' => $item->sell_unit,
                    'quantity' => (float) $item->quantity,
                    'unit_price_override' => $item->unit_price_override !== null ? (float) $item->unit_price_override : null,
                ])->values()->all(),
            ])
            ->values()
            ->all();
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
}
