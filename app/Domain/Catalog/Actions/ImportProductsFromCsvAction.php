<?php

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Manufacturer;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Services\ProductService;
use App\Support\Catalog\ProductCatalogOptions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ImportProductsFromCsvAction
{
    public function __construct(private readonly ProductService $products) {}

    /**
     * @return array{created:int,skipped:int,errors:list<array{row:int,messages:list<string>}>}
     */
    public function execute(UploadedFile $file, bool $skipDuplicates = true): array
    {
        $preview = $this->preview($file);
        $created = 0;
        $skipped = 0;
        $errors = [];

        DB::transaction(function () use ($preview, $skipDuplicates, &$created, &$skipped, &$errors) {
            foreach ($preview['rows'] as $entry) {
                if (! empty($entry['errors'])) {
                    $errors[] = ['row' => $entry['row'], 'messages' => $entry['errors']];

                    continue;
                }

                $data = $entry['data'];
                $exists = Product::query()->where('sku', $data['sku'])->exists();
                if ($exists) {
                    if ($skipDuplicates) {
                        $skipped++;

                        continue;
                    }
                    $errors[] = ['row' => $entry['row'], 'messages' => ["SKU [{$data['sku']}] already exists."]];

                    continue;
                }

                $this->products->createProduct($data);
                $created++;
            }
        });

        activity()
            ->causedBy(auth()->user())
            ->withProperties(['created' => $created, 'skipped' => $skipped, 'errors' => count($errors)])
            ->event('catalog.products_imported')
            ->log('Products imported from CSV');

        return compact('created', 'skipped', 'errors');
    }

    /**
     * @return array{headers:list<string>,rows:list<array{row:int,data:array<string,mixed>,errors:list<string>}>,valid_count:int,error_count:int}
     */
    public function preview(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            return ['headers' => [], 'rows' => [], 'valid_count' => 0, 'error_count' => 0];
        }

        $headers = array_map(fn ($h) => Str::snake(trim((string) $h)), fgetcsv($handle) ?: []);
        $rows = [];
        $rowNum = 1;
        $validCount = 0;
        $errorCount = 0;

        while (($line = fgetcsv($handle)) !== false) {
            $rowNum++;
            if ($line === [null] || $line === []) {
                continue;
            }

            $assoc = [];
            foreach ($headers as $i => $key) {
                if ($key !== '') {
                    $assoc[$key] = trim((string) ($line[$i] ?? ''));
                }
            }

            $normalized = $this->normalizeRow($assoc);
            $errors = $this->validateRow($normalized);

            if ($errors === []) {
                $validCount++;
            } else {
                $errorCount++;
            }

            $rows[] = ['row' => $rowNum, 'data' => $normalized, 'errors' => $errors];

            if (count($rows) >= 500) {
                break;
            }
        }

        fclose($handle);

        return [
            'headers' => $headers,
            'rows' => $rows,
            'valid_count' => $validCount,
            'error_count' => $errorCount,
        ];
    }

    /**
     * @param  array<string, string>  $row
     * @return array<string, mixed>
     */
    private function normalizeRow(array $row): array
    {
        $baseUnit = strtolower($row['base_unit'] ?? 'strip');
        $purchase = (float) ($row['purchase_price'] ?? 0);
        $sale = (float) ($row['sale_price'] ?? 0);

        $categoryId = null;
        if (filled($row['category_slug'] ?? null)) {
            $categoryId = Category::query()->where('slug', $row['category_slug'])->value('id');
        }

        $manufacturerId = null;
        if (filled($row['manufacturer_name'] ?? null)) {
            $manufacturer = Manufacturer::query()->firstOrCreate(['name' => $row['manufacturer_name']]);
            $manufacturerId = $manufacturer->getKey();
        }

        return [
            'name' => $row['name'] ?? '',
            'sku' => strtoupper($row['sku'] ?? ''),
            'barcode' => $row['barcode'] ?? null,
            'product_type' => strtolower($row['product_type'] ?? 'other'),
            'base_unit' => $baseUnit,
            'category_id' => $categoryId,
            'manufacturer_id' => $manufacturerId,
            'purchase_price' => $purchase,
            'sale_price' => $sale,
            'min_stock' => (int) ($row['min_stock'] ?? 0),
            'is_active' => ! in_array(strtolower($row['is_active'] ?? '1'), ['0', 'false', 'no'], true),
            'units' => [[
                'sell_unit' => $baseUnit,
                'conversion_factor' => 1,
                'purchase_price' => $purchase,
                'sale_price' => $sale,
                'is_default' => true,
            ]],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    private function validateRow(array $data): array
    {
        $errors = [];

        if ($data['name'] === '') {
            $errors[] = 'Name is required.';
        }
        if ($data['sku'] === '') {
            $errors[] = 'SKU is required.';
        }
        if (! in_array($data['product_type'], ProductCatalogOptions::productTypes(), true)) {
            $errors[] = 'Invalid product_type.';
        }
        if (! in_array($data['base_unit'], ProductCatalogOptions::sellUnits(), true)) {
            $errors[] = 'Invalid base_unit.';
        }

        return $errors;
    }
}
