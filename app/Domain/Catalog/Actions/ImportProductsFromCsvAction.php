<?php

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Manufacturer;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\StorageLocation;
use App\Domain\Catalog\Services\ProductService;
use App\Support\Catalog\ProductCatalogOptions;
use App\Support\Tenant\TenantFeatures;
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
                if ($skipDuplicates && filled($data['sku'] ?? null)) {
                    $exists = Product::query()->where('sku', $data['sku'])->exists();
                    if ($exists) {
                        $skipped++;

                        continue;
                    }
                }

                if ($skipDuplicates && ! filled($data['sku'] ?? null)) {
                    $exists = Product::query()->where('name', $data['name'])->exists();
                    if ($exists) {
                        $skipped++;

                        continue;
                    }
                }

                if (! $skipDuplicates && filled($data['sku'] ?? null) && Product::query()->where('sku', $data['sku'])->exists()) {
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
     * @return array{headers:list<string>,rows:list<array{row:int,data:array<string,mixed>,raw:array<string,string>,errors:list<string>}>,valid_count:int,error_count:int}
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
            $errors = $this->validateRow($normalized, $assoc);

            if ($errors === []) {
                $validCount++;
            } else {
                $errorCount++;
            }

            $rows[] = ['row' => $rowNum, 'data' => $normalized, 'raw' => $assoc, 'errors' => $errors];

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

        $storageLocationId = null;
        if (filled($row['storage_location_code'] ?? null)) {
            $storageLocationId = StorageLocation::query()
                ->where('code', $row['storage_location_code'])
                ->value('id');
        }

        $sku = filled($row['sku'] ?? null) ? strtoupper($row['sku']) : null;

        $data = [
            'name' => $row['name'] ?? '',
            'generic_name' => filled($row['generic_name'] ?? null) ? $row['generic_name'] : null,
            'sku' => $sku,
            'barcode' => filled($row['barcode'] ?? null) ? $row['barcode'] : null,
            'product_type' => strtolower($row['product_type'] ?? 'other'),
            'base_unit' => $baseUnit,
            'category_id' => $categoryId,
            'manufacturer_id' => $manufacturerId,
            'storage_location_id' => $storageLocationId,
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

        if (filled($row['pieces_per_strip'] ?? null)) {
            $data['pieces_per_strip'] = (float) $row['pieces_per_strip'];
        }
        if (filled($row['strips_per_box'] ?? null)) {
            $data['strips_per_box'] = (float) $row['strips_per_box'];
        }
        if (filled($row['boxes_per_carton'] ?? null)) {
            $data['boxes_per_carton'] = (float) $row['boxes_per_carton'];
        }
        if (filled($row['short_description'] ?? null)) {
            $data['short_description'] = $row['short_description'];
        }
        if (filled($row['vat_percent'] ?? null)) {
            $data['vat_percent'] = (float) $row['vat_percent'];
        }
        if (TenantFeatures::wholesalePricingEnabled(tenant()) && filled($row['wholesale_price'] ?? null)) {
            $data['wholesale_price'] = (float) $row['wholesale_price'];
        }
        if (filled($row['opening_quantity'] ?? null)) {
            $data['opening_quantity'] = (float) $row['opening_quantity'];
        }
        if (filled($row['opening_batch_no'] ?? null)) {
            $data['opening_batch_no'] = $row['opening_batch_no'];
        }
        if (filled($row['opening_expiry_date'] ?? null)) {
            $data['opening_expiry_date'] = $row['opening_expiry_date'];
        }
        if ($storageLocationId !== null && filled($row['opening_quantity'] ?? null)) {
            $data['opening_storage_location_id'] = $storageLocationId;
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $raw
     * @return list<string>
     */
    private function validateRow(array $data, array $raw): array
    {
        $errors = [];

        if ($data['name'] === '') {
            $errors[] = 'Name is required.';
        }
        if (! in_array($data['product_type'], ProductCatalogOptions::productTypes(), true)) {
            $errors[] = 'Invalid product_type.';
        }
        if (! in_array($data['base_unit'], ProductCatalogOptions::sellUnits(), true)) {
            $errors[] = 'Invalid base_unit.';
        }
        if (filled($raw['category_slug'] ?? null) && $data['category_id'] === null) {
            $errors[] = 'Unknown category_slug.';
        }
        if (filled($raw['storage_location_code'] ?? null) && $data['storage_location_id'] === null) {
            $errors[] = 'Unknown storage_location_code.';
        }
        if (filled($raw['opening_expiry_date'] ?? null) && strtotime($raw['opening_expiry_date']) === false) {
            $errors[] = 'Invalid opening_expiry_date (use YYYY-MM-DD).';
        }

        return $errors;
    }
}
