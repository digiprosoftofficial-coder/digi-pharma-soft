<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Catalog\Actions\ImportProductsFromCsvAction;
use App\Domain\Catalog\Models\Product;
use App\Http\Controllers\Controller;
use App\Support\Catalog\ProductImportCsv;
use App\Support\Tenant\TenantFeatures;
use App\Support\Tenant\TenantLimits;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ProductImportController extends Controller
{
    public function __construct(private readonly ImportProductsFromCsvAction $import) {}

    public function index(): Response
    {
        $this->authorizeImport();

        return Inertia::render('Catalog/Import/Index', [
            'preview' => session('import_preview'),
            'csvColumns' => $this->columns(),
            'importPreset' => TenantFeatures::importPreset(tenant()),
            'maxImportRows' => TenantLimits::maxImportRows(tenant()),
            'remainingProducts' => TenantLimits::remainingProducts(tenant()),
        ]);
    }

    public function sample(): StreamedResponse
    {
        $this->authorizeImport();

        $headers = $this->columns();

        return response()->streamDownload(function () use ($headers) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            foreach (ProductImportCsv::sampleRowsFor($headers) as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, 'product-import-sample.csv', ['Content-Type' => 'text/csv']);
    }

    public function preview(Request $request): RedirectResponse
    {
        $this->authorizeImport();

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $preview = $this->import->preview($request->file('file'));

        return back()->with('import_preview', $preview);
    }

    public function revalidate(Request $request): RedirectResponse
    {
        $this->authorizeImport();

        $validated = $request->validate([
            'headers' => ['required', 'array', 'min:1'],
            'headers.*' => ['required', 'string', 'max:64'],
            'rows' => ['required', 'array', 'min:1', 'max:500'],
            'rows.*.row' => ['required', 'integer', 'min:2'],
            'rows.*.raw' => ['required', 'array'],
        ]);

        $preview = $this->import->previewFromRows(
            $validated['headers'],
            $validated['rows'],
        );

        return back()->with('import_preview', $preview);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeImport();

        $hasRows = $request->has('rows');

        $request->validate([
            'file' => [$hasRows ? 'nullable' : 'required', 'file', 'mimes:csv,txt', 'max:2048'],
            'headers' => ['required_with:rows', 'array', 'min:1'],
            'headers.*' => ['required', 'string', 'max:64'],
            'rows' => ['required_without:file', 'array', 'min:1', 'max:500'],
            'rows.*.row' => ['required', 'integer', 'min:2'],
            'rows.*.raw' => ['required', 'array'],
            'skip_duplicates' => ['sometimes', 'boolean'],
        ]);

        $skipDuplicates = $request->boolean('skip_duplicates', true);
        $maxRows = TenantLimits::maxImportRows(tenant());

        if ($hasRows) {
            $rowCount = count($request->input('rows', []));
            if ($maxRows !== null && $rowCount > $maxRows) {
                return back()->withErrors([
                    'rows' => __('catalog.import_rows_limit', ['max' => $maxRows, 'rows' => $rowCount]),
                ]);
            }

            $result = $this->import->executeFromRows(
                $request->input('headers'),
                $request->input('rows'),
                $skipDuplicates,
            );
        } else {
            $file = $request->file('file');

            if ($maxRows !== null) {
                $rowCount = $this->import->dataRowCount($file);
                if ($rowCount > $maxRows) {
                    return back()->withErrors([
                        'file' => __('catalog.import_rows_limit', ['max' => $maxRows, 'rows' => $rowCount]),
                    ]);
                }
            }

            $result = $this->import->execute($file, $skipDuplicates);
        }

        return redirect()
            ->route('tenant.catalog.import.index')
            ->with('success', __('catalog.import_complete', [
                'created' => $result['created'],
                'skipped' => $result['skipped'],
                'errors' => count($result['errors']),
            ]));
    }

    private function authorizeImport(): void
    {
        $this->authorize('create', Product::class);

        abort_unless(TenantFeatures::bulkImportEnabled(tenant()), 403);
    }

    /**
     * @return list<string>
     */
    private function columns(): array
    {
        return ProductImportCsv::columnsForPreset(
            TenantFeatures::importPreset(tenant()),
            TenantFeatures::advancedCatalogEnabled(tenant()),
            TenantFeatures::wholesalePricingEnabled(tenant()),
            TenantFeatures::importColumns(tenant()),
        );
    }
}
