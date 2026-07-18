<?php

namespace App\Http\Controllers\Central;

use App\Domain\Catalog\Actions\ImportMasterProductsFromCsvAction;
use App\Domain\Catalog\Models\MasterProduct;
use App\Domain\Catalog\Models\Product;
use App\Http\Controllers\Controller;
use App\Support\Catalog\MasterProductImportCsv;
use App\Support\Catalog\ProductCatalogOptions;
use App\Support\Catalog\ProductType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PlatformMasterCatalogController extends Controller
{
    public function __construct(
        private readonly ImportMasterProductsFromCsvAction $import,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', MasterProduct::class);

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:all,active,inactive'],
            'product_type' => ['nullable', 'string', 'max:64'],
        ]);

        $q = trim((string) ($validated['q'] ?? ''));
        $status = $validated['status'] ?? 'all';
        $type = $validated['product_type'] ?? null;

        $query = MasterProduct::query()->orderBy('name');

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', '%'.$q.'%')
                    ->orWhere('generic_name', 'like', '%'.$q.'%')
                    ->orWhere('strength', 'like', '%'.$q.'%')
                    ->orWhere('manufacturer_name', 'like', '%'.$q.'%')
                    ->orWhere('sku', 'like', '%'.$q.'%')
                    ->orWhere('barcode', $q)
                    ->orWhere('drug_class', 'like', '%'.$q.'%');
            });
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        if (filled($type)) {
            $query->where('product_type', $type);
        }

        $products = $query->paginate(25)->withQueryString()->through(fn (MasterProduct $p) => $this->toRow($p));

        return Inertia::render('Platform/MasterCatalog/Index', [
            'products' => $products,
            'filters' => [
                'q' => $q,
                'status' => $status,
                'product_type' => $type,
            ],
            'stats' => [
                'total' => MasterProduct::query()->count(),
                'active' => MasterProduct::query()->where('is_active', true)->count(),
                'inactive' => MasterProduct::query()->where('is_active', false)->count(),
                'manufacturers' => (int) MasterProduct::query()
                    ->whereNotNull('manufacturer_name')
                    ->where('manufacturer_name', '!=', '')
                    ->selectRaw('count(distinct manufacturer_name) as aggregate')
                    ->value('aggregate'),
            ],
            'productTypes' => ProductType::values(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', MasterProduct::class);

        return Inertia::render('Platform/MasterCatalog/Form', [
            'product' => null,
            'productTypes' => ProductType::values(),
            'sellUnits' => ProductCatalogOptions::sellUnits(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', MasterProduct::class);

        $data = $this->validated($request);
        if (blank($data['sku'] ?? null)) {
            $data['sku'] = $this->uniqueSku($data['name']);
        }

        MasterProduct::query()->create($data);

        return redirect()
            ->route('platform.master-catalog.index')
            ->with('success', __('platform.master_created'));
    }

    public function edit(MasterProduct $masterProduct): Response
    {
        $this->authorize('update', $masterProduct);

        return Inertia::render('Platform/MasterCatalog/Form', [
            'product' => $this->toRow($masterProduct),
            'productTypes' => ProductType::values(),
            'sellUnits' => ProductCatalogOptions::sellUnits(),
            'activatedCount' => Product::query()
                ->withoutGlobalScopes()
                ->where('master_product_id', $masterProduct->getKey())
                ->count(),
        ]);
    }

    public function update(Request $request, MasterProduct $masterProduct): RedirectResponse
    {
        $this->authorize('update', $masterProduct);

        $data = $this->validated($request, $masterProduct);
        $masterProduct->fill($data)->save();

        return redirect()
            ->route('platform.master-catalog.index')
            ->with('success', __('platform.master_updated'));
    }

    public function destroy(MasterProduct $masterProduct): RedirectResponse
    {
        $this->authorize('delete', $masterProduct);

        $inUse = Product::query()
            ->withoutGlobalScopes()
            ->where('master_product_id', $masterProduct->getKey())
            ->exists();

        if ($inUse) {
            $masterProduct->update(['is_active' => false]);

            return redirect()
                ->route('platform.master-catalog.index')
                ->with('success', __('platform.master_deactivated_in_use'));
        }

        $masterProduct->delete();

        return redirect()
            ->route('platform.master-catalog.index')
            ->with('success', __('platform.master_deleted'));
    }

    public function importForm(): Response
    {
        $this->authorize('create', MasterProduct::class);

        return Inertia::render('Platform/MasterCatalog/Import', [
            'preview' => session('master_import_preview'),
            'csvColumns' => MasterProductImportCsv::HEADERS,
            'maxRows' => ImportMasterProductsFromCsvAction::MAX_ROWS,
            'stats' => [
                'total' => MasterProduct::query()->count(),
            ],
        ]);
    }

    public function sample(): StreamedResponse
    {
        $this->authorize('create', MasterProduct::class);

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, MasterProductImportCsv::HEADERS);
            foreach (MasterProductImportCsv::sampleRows() as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, 'master-medicine-sample.csv', ['Content-Type' => 'text/csv']);
    }

    public function preview(Request $request): RedirectResponse
    {
        $this->authorize('create', MasterProduct::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        $preview = $this->import->preview($request->file('file'));

        if (count($preview['rows']) > ImportMasterProductsFromCsvAction::MAX_ROWS) {
            return back()->withErrors([
                'file' => __('platform.master_import_rows_limit', [
                    'max' => ImportMasterProductsFromCsvAction::MAX_ROWS,
                    'rows' => count($preview['rows']),
                ]),
            ]);
        }

        return back()->with('master_import_preview', $preview);
    }

    public function importStore(Request $request): RedirectResponse
    {
        $this->authorize('create', MasterProduct::class);

        $request->validate([
            'headers' => ['required', 'array', 'min:1'],
            'headers.*' => ['required', 'string', 'max:64'],
            'rows' => ['required', 'array', 'min:1', 'max:'.ImportMasterProductsFromCsvAction::MAX_ROWS],
            'rows.*.row' => ['required', 'integer', 'min:2'],
            'rows.*.raw' => ['required', 'array'],
            'update_existing' => ['sometimes', 'boolean'],
        ]);

        $preview = $this->import->previewFromRows(
            $request->input('headers'),
            $request->input('rows'),
        );

        $result = $this->import->executeFromPreview(
            $preview,
            $request->boolean('update_existing', true),
        );

        return redirect()
            ->route('platform.master-catalog.index')
            ->with('success', __('platform.master_import_complete', [
                'created' => $result['created'],
                'updated' => $result['updated'],
                'skipped' => $result['skipped'],
            ]));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?MasterProduct $existing = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'generic_name' => ['nullable', 'string', 'max:255'],
            'strength' => ['nullable', 'string', 'max:64'],
            'manufacturer_name' => ['nullable', 'string', 'max:255'],
            'product_type' => ['required', Rule::in(ProductType::values())],
            'drug_class' => ['nullable', 'string', 'max:255'],
            'base_unit' => ['required', ProductCatalogOptions::sellUnitRule()],
            'pieces_per_strip' => ['nullable', 'numeric', 'min:0.0001'],
            'strips_per_box' => ['nullable', 'numeric', 'min:0.0001'],
            'boxes_per_carton' => ['nullable', 'numeric', 'min:0.0001'],
            'sku' => [
                'nullable',
                'string',
                'max:64',
                Rule::unique('master_products', 'sku')->ignore($existing?->getKey()),
            ],
            'barcode' => ['nullable', 'string', 'max:64'],
            'mrp' => ['required', 'numeric', 'min:0'],
            'default_purchase_price' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        if (! isset($data['default_purchase_price']) || $data['default_purchase_price'] === null || $data['default_purchase_price'] === '') {
            $data['default_purchase_price'] = round(((float) $data['mrp']) * 0.85, 4);
        }

        foreach (['generic_name', 'strength', 'manufacturer_name', 'drug_class', 'sku', 'barcode', 'pieces_per_strip', 'strips_per_box', 'boxes_per_carton'] as $field) {
            if (array_key_exists($field, $data) && ($data[$field] === '' || $data[$field] === null)) {
                $data[$field] = null;
            }
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function toRow(MasterProduct $p): array
    {
        return [
            'id' => $p->id,
            'name' => $p->name,
            'generic_name' => $p->generic_name,
            'strength' => $p->strength,
            'manufacturer_name' => $p->manufacturer_name,
            'product_type' => $p->product_type,
            'drug_class' => $p->drug_class,
            'base_unit' => $p->base_unit,
            'pieces_per_strip' => $p->pieces_per_strip !== null ? (string) $p->pieces_per_strip : null,
            'strips_per_box' => $p->strips_per_box !== null ? (string) $p->strips_per_box : null,
            'boxes_per_carton' => $p->boxes_per_carton !== null ? (string) $p->boxes_per_carton : null,
            'sku' => $p->sku,
            'barcode' => $p->barcode,
            'mrp' => (string) $p->mrp,
            'default_purchase_price' => (string) $p->default_purchase_price,
            'is_active' => (bool) $p->is_active,
        ];
    }

    private function uniqueSku(string $name): string
    {
        $base = 'MSTR-'.Str::upper(Str::slug(Str::limit($name, 40, ''), ''));
        $candidate = $base !== 'MSTR-' ? $base : 'MSTR-'.Str::upper(Str::random(8));
        $suffix = 1;

        while (MasterProduct::query()->where('sku', $candidate)->exists()) {
            $candidate = $base.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }
}
