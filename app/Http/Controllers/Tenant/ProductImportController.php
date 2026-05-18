<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Catalog\Actions\ImportProductsFromCsvAction;
use App\Domain\Catalog\Models\Product;
use App\Http\Controllers\Controller;
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
        $this->authorize('create', Product::class);

        return Inertia::render('Catalog/Import/Index', [
            'preview' => session('import_preview'),
        ]);
    }

    public function sample(): StreamedResponse
    {
        $this->authorize('create', Product::class);

        $headers = [
            'name', 'sku', 'barcode', 'product_type', 'base_unit',
            'category_slug', 'manufacturer_name', 'purchase_price', 'sale_price', 'min_stock', 'is_active',
        ];

        return response()->streamDownload(function () use ($headers) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            fputcsv($out, ['Paracetamol 500mg', 'PAR-500', '8801234567890', 'tablet', 'strip', 'general', 'Demo Labs', '20', '35', '10', '1']);
            fclose($out);
        }, 'product-import-sample.csv', ['Content-Type' => 'text/csv']);
    }

    public function preview(Request $request): RedirectResponse
    {
        $this->authorize('create', Product::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $preview = $this->import->preview($request->file('file'));

        return back()->with('import_preview', $preview);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Product::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
            'skip_duplicates' => ['sometimes', 'boolean'],
        ]);

        $result = $this->import->execute(
            $request->file('file'),
            $request->boolean('skip_duplicates', true),
        );

        return redirect()
            ->route('tenant.catalog.import.index')
            ->with('success', __('catalog.import_complete', [
                'created' => $result['created'],
                'skipped' => $result['skipped'],
                'errors' => count($result['errors']),
            ]));
    }
}
