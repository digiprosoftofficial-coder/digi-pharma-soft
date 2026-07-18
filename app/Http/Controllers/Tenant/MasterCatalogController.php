<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Catalog\Models\MasterProduct;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Services\MasterProductActivationService;
use App\Http\Controllers\Controller;
use App\Http\Resources\Catalog\MasterProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

final class MasterCatalogController extends Controller
{
    public function __construct(private readonly MasterProductActivationService $activation) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('products.view'), 403);

        return Inertia::render('Catalog/MasterCatalog/Index', [
            'initialResults' => $this->decorate(
                MasterProduct::query()
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->limit(30)
                    ->get()
            ),
            'totalCount' => MasterProduct::query()->where('is_active', true)->count(),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('products.view'), 403);

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $term = trim((string) ($validated['q'] ?? ''));

        $query = MasterProduct::query()->where('is_active', true);

        if ($term !== '') {
            $query->where(function ($w) use ($term) {
                $w->where('name', 'like', '%'.$term.'%')
                    ->orWhere('generic_name', 'like', '%'.$term.'%')
                    ->orWhere('strength', 'like', '%'.$term.'%')
                    ->orWhere('manufacturer_name', 'like', '%'.$term.'%')
                    ->orWhere('sku', 'like', '%'.$term.'%')
                    ->orWhere('barcode', $term);
            });
        }

        $results = $query->orderBy('name')->limit(50)->get();

        return response()->json([
            'data' => $this->decorate($results),
        ]);
    }

    public function activate(Request $request, MasterProduct $masterProduct): JsonResponse
    {
        abort_unless($request->user()?->can('products.manage'), 403);

        $tenantId = tenant_id();
        abort_if($tenantId === null, 403);

        $product = $this->activation->activate($masterProduct, $tenantId);

        return response()->json([
            'ok' => true,
            'product_id' => $product->getKey(),
            'name' => $product->name,
        ]);
    }

    /**
     * Attach per-tenant activation status to master products.
     *
     * @param  Collection<int, MasterProduct>  $masterProducts
     * @return array<int, array<string, mixed>>
     */
    private function decorate(Collection $masterProducts): array
    {
        $tenantId = tenant_id();

        $masterIds = $masterProducts->pluck('id')->all();
        $barcodes = $masterProducts->pluck('barcode')->filter()->values()->all();

        $activatedByMaster = [];
        $activatedByBarcode = [];

        if ($tenantId !== null && $masterProducts->isNotEmpty()) {
            Product::query()
                ->where('tenant_id', $tenantId)
                ->where(function ($w) use ($masterIds, $barcodes) {
                    $w->whereIn('master_product_id', $masterIds);
                    if ($barcodes !== []) {
                        $w->orWhereIn('barcode', $barcodes);
                    }
                })
                ->get(['id', 'master_product_id', 'barcode'])
                ->each(function (Product $p) use (&$activatedByMaster, &$activatedByBarcode) {
                    if ($p->master_product_id !== null) {
                        $activatedByMaster[(int) $p->master_product_id] = (int) $p->id;
                    }
                    if (filled($p->barcode)) {
                        $activatedByBarcode[$p->barcode] = (int) $p->id;
                    }
                });
        }

        return $masterProducts->map(function (MasterProduct $master) use ($activatedByMaster, $activatedByBarcode) {
            $tenantProductId = $activatedByMaster[$master->id]
                ?? (filled($master->barcode) ? ($activatedByBarcode[$master->barcode] ?? null) : null);

            $master->setAttribute('is_activated', $tenantProductId !== null);
            $master->setAttribute('tenant_product_id', $tenantProductId);

            return (new MasterProductResource($master))->resolve();
        })->all();
    }
}
