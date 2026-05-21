<?php

namespace App\Domain\Catalog\Services;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductBatch;
use App\Domain\Catalog\Repositories\ProductRepository;
use App\Domain\Inventory\Models\StockMovement;
use App\Support\Catalog\ProductCatalogOptions;
use App\Support\Catalog\ProductImageStorage;
use App\Support\Catalog\ProductSkuGenerator;
use App\Support\Catalog\ProductStockCalculator;
use App\Support\Catalog\ProductUnitResolver;
use App\Support\Tenant\TenantFeatures;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ProductService
{
    public function __construct(private readonly ProductRepository $products) {}

    public function createProduct(array $data, ?UploadedFile $image = null): Product
    {
        return DB::transaction(function () use ($data, $image) {
            $sku = filled($data['sku'] ?? null)
                ? (string) $data['sku']
                : ProductSkuGenerator::generate();
            $barcode = $data['barcode'] ?? null;
            if ($barcode === null || $barcode === '') {
                $barcode = 'BC-'.Str::upper(Str::slug($sku, '')).'-'.Str::upper(Str::random(4));
            }

            $baseUnit = $data['base_unit'] ?? 'strip';
            $piecesPerStrip = isset($data['pieces_per_strip']) ? (float) $data['pieces_per_strip'] : null;
            $stripsPerBox = isset($data['strips_per_box']) ? (float) $data['strips_per_box'] : null;
            $boxesPerCarton = isset($data['boxes_per_carton']) ? (float) $data['boxes_per_carton'] : null;
            $units = $this->normalizeUnitsPayload(
                $data['units'] ?? [],
                $baseUnit,
                $piecesPerStrip,
                $stripsPerBox,
                $boxesPerCarton,
            );

            $default = collect($units)->firstWhere('is_default', true) ?? $units[0];

            $product = $this->products->store([
                'category_id' => $data['category_id'] ?? null,
                'manufacturer_id' => $data['manufacturer_id'] ?? null,
                'storage_location_id' => $data['storage_location_id'] ?? null,
                'name' => $data['name'],
                'generic_name' => $this->normalizeOptionalString($data['generic_name'] ?? null),
                'sku' => $sku,
                'barcode' => $barcode,
                'product_type' => $data['product_type'] ?? 'other',
                'base_unit' => $baseUnit,
                'pieces_per_strip' => $this->normalizePiecesPerStrip($data['pieces_per_strip'] ?? null),
                'strips_per_box' => $this->normalizeStripsPerBox($data['strips_per_box'] ?? null),
                'boxes_per_carton' => $this->normalizeBoxesPerCarton($data['boxes_per_carton'] ?? null),
                'unit' => $default['sell_unit'],
                'purchase_price' => $default['purchase_price'],
                'sale_price' => $default['sale_price'],
                'wholesale_price' => TenantFeatures::wholesalePricingEnabled(tenant())
                    ? $this->normalizeOptionalDecimal($data['wholesale_price'] ?? null)
                    : null,
                'vat_percent' => $this->normalizeOptionalDecimal($data['vat_percent'] ?? null),
                'short_description' => $this->normalizeOptionalString($data['short_description'] ?? null),
                'image_path' => $image ? ProductImageStorage::store($image) : null,
                'min_stock' => $data['min_stock'] ?? 0,
                'is_active' => $data['is_active'] ?? true,
            ]);

            ProductUnitResolver::syncProductUnits($product, $units);

            $this->createOpeningBatchIfProvided($product, $data, $default);

            return $product->fresh(['units', 'batches']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $defaultUnit
     */
    private function createOpeningBatchIfProvided(Product $product, array $data, array $defaultUnit): void
    {
        $quantity = $data['opening_quantity'] ?? null;
        if ($quantity === null || $quantity === '' || (float) $quantity <= 0) {
            return;
        }

        $batchNo = filled($data['opening_batch_no'] ?? null)
            ? (string) $data['opening_batch_no']
            : 'OPEN-'.strtoupper($product->sku);

        $storageLocationId = $data['opening_storage_location_id']
            ?? $data['storage_location_id']
            ?? null;

        ProductBatch::query()->create([
            'product_id' => $product->getKey(),
            'batch_no' => $batchNo,
            'expiry_date' => $data['opening_expiry_date'] ?? null,
            'quantity_on_hand' => $quantity,
            'purchase_unit_cost' => $defaultUnit['purchase_price'] ?? 0,
            'storage_location_id' => $storageLocationId,
        ]);
    }

    public function updateProduct(Product $product, array $data, ?UploadedFile $image = null): Product
    {
        return DB::transaction(function () use ($product, $data, $image) {
            $baseUnit = $data['base_unit'] ?? $product->base_unit;
            $piecesPerStrip = array_key_exists('pieces_per_strip', $data)
                ? (isset($data['pieces_per_strip']) ? (float) $data['pieces_per_strip'] : null)
                : null;
            $stripsPerBox = array_key_exists('strips_per_box', $data)
                ? (isset($data['strips_per_box']) ? (float) $data['strips_per_box'] : null)
                : null;
            $boxesPerCarton = array_key_exists('boxes_per_carton', $data)
                ? (isset($data['boxes_per_carton']) ? (float) $data['boxes_per_carton'] : null)
                : null;

            if (isset($data['units'])) {
                $units = $this->normalizeUnitsPayload($data['units'], $baseUnit, $piecesPerStrip, $stripsPerBox, $boxesPerCarton);
            } elseif (
                ($piecesPerStrip !== null && $piecesPerStrip > 0)
                || ($stripsPerBox !== null && $stripsPerBox > 0)
                || ($boxesPerCarton !== null && $boxesPerCarton > 0)
            ) {
                $existingUnits = $product->units->map(fn ($u) => [
                    'sell_unit' => $u->sell_unit,
                    'conversion_factor' => (float) $u->conversion_factor,
                    'purchase_price' => $u->purchase_price,
                    'sale_price' => $u->sale_price,
                    'is_default' => (bool) $u->is_default,
                ])->all();
                $units = $this->normalizeUnitsPayload(
                    $existingUnits,
                    $baseUnit,
                    $piecesPerStrip ?? ($product->pieces_per_strip !== null ? (float) $product->pieces_per_strip : null),
                    $stripsPerBox ?? ($product->strips_per_box !== null ? (float) $product->strips_per_box : null),
                    $boxesPerCarton ?? ($product->boxes_per_carton !== null ? (float) $product->boxes_per_carton : null),
                );
            } else {
                $units = null;
            }

            $default = $units
                ? (collect($units)->firstWhere('is_default', true) ?? $units[0])
                : null;

            $imagePath = $product->image_path;
            if (! empty($data['remove_image'])) {
                ProductImageStorage::delete($imagePath);
                $imagePath = null;
            }
            if ($image) {
                ProductImageStorage::delete($imagePath);
                $imagePath = ProductImageStorage::store($image);
            }

            $this->products->update($product, [
                'category_id' => $data['category_id'] ?? $product->category_id,
                'manufacturer_id' => $data['manufacturer_id'] ?? $product->manufacturer_id,
                'storage_location_id' => array_key_exists('storage_location_id', $data)
                    ? ($data['storage_location_id'] ?: null)
                    : $product->storage_location_id,
                'name' => $data['name'] ?? $product->name,
                'generic_name' => array_key_exists('generic_name', $data)
                    ? $this->normalizeOptionalString($data['generic_name'])
                    : $product->generic_name,
                'sku' => $data['sku'] ?? $product->sku,
                'barcode' => array_key_exists('barcode', $data) ? $data['barcode'] : $product->barcode,
                'product_type' => $data['product_type'] ?? $product->product_type,
                'base_unit' => $baseUnit,
                'pieces_per_strip' => array_key_exists('pieces_per_strip', $data)
                    ? $this->normalizePiecesPerStrip($data['pieces_per_strip'])
                    : $product->pieces_per_strip,
                'strips_per_box' => array_key_exists('strips_per_box', $data)
                    ? $this->normalizeStripsPerBox($data['strips_per_box'])
                    : $product->strips_per_box,
                'boxes_per_carton' => array_key_exists('boxes_per_carton', $data)
                    ? $this->normalizeBoxesPerCarton($data['boxes_per_carton'])
                    : $product->boxes_per_carton,
                'unit' => $default['sell_unit'] ?? $product->unit,
                'purchase_price' => $default['purchase_price'] ?? $product->purchase_price,
                'sale_price' => $default['sale_price'] ?? $product->sale_price,
                'wholesale_price' => TenantFeatures::wholesalePricingEnabled(tenant()) && array_key_exists('wholesale_price', $data)
                    ? $this->normalizeOptionalDecimal($data['wholesale_price'])
                    : $product->wholesale_price,
                'vat_percent' => array_key_exists('vat_percent', $data)
                    ? $this->normalizeOptionalDecimal($data['vat_percent'])
                    : $product->vat_percent,
                'short_description' => array_key_exists('short_description', $data)
                    ? $this->normalizeOptionalString($data['short_description'])
                    : $product->short_description,
                'image_path' => $imagePath,
                'min_stock' => $data['min_stock'] ?? $product->min_stock,
                'is_active' => $data['is_active'] ?? $product->is_active,
            ]);

            if ($units !== null) {
                ProductUnitResolver::syncProductUnits($product->fresh(), $units);
            }

            $this->applyStockAdjustmentIfProvided($product->fresh(['units', 'batches']), $data);
            $this->syncBatchLocations($product->fresh(), $data);

            return $product->fresh(['units', 'batches', 'batches.storageLocation', 'storageLocation']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function applyStockAdjustmentIfProvided(Product $product, array $data): void
    {
        if (! array_key_exists('stock_adjustment', $data)) {
            return;
        }

        $delta = $data['stock_adjustment'];
        if ($delta === null || $delta === '') {
            return;
        }

        $delta = (float) $delta;
        if ($delta === 0.0) {
            return;
        }

        $batchId = $data['stock_adjust_batch_id'] ?? null;
        $batch = null;

        if ($batchId) {
            $batch = ProductBatch::query()
                ->where('product_id', $product->getKey())
                ->whereKey($batchId)
                ->lockForUpdate()
                ->first();

            if ($batch === null) {
                throw ValidationException::withMessages([
                    'stock_adjust_batch_id' => [__('catalog.stock_adjust_batch_invalid')],
                ]);
            }
        } else {
            $batches = ProductBatch::query()
                ->where('product_id', $product->getKey())
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($batches->count() === 1) {
                $batch = $batches->first();
            } elseif ($batches->count() > 1) {
                throw ValidationException::withMessages([
                    'stock_adjust_batch_id' => [__('catalog.stock_adjust_batch_required')],
                ]);
            }
        }

        if ($batch === null) {
            if ($delta <= 0) {
                throw ValidationException::withMessages([
                    'stock_adjustment' => [__('catalog.stock_adjustment_no_batch')],
                ]);
            }

            $batchNo = filled($data['stock_adjust_batch_no'] ?? null)
                ? (string) $data['stock_adjust_batch_no']
                : 'ADJ-'.strtoupper($product->sku);

            $defaultUnit = $product->defaultUnit();

            $batch = ProductBatch::query()->create([
                'product_id' => $product->getKey(),
                'batch_no' => $batchNo,
                'quantity_on_hand' => 0,
                'purchase_unit_cost' => $defaultUnit?->purchase_price ?? $product->purchase_price,
                'storage_location_id' => $product->storage_location_id,
            ]);
        }

        $newQty = (float) $batch->quantity_on_hand + $delta;
        if ($newQty < 0) {
            throw ValidationException::withMessages([
                'stock_adjustment' => [__('catalog.stock_adjustment_below_zero')],
            ]);
        }

        $batch->quantity_on_hand = (string) $newQty;
        $batch->save();

        StockMovement::query()->create([
            'product_batch_id' => $batch->getKey(),
            'type' => 'adjustment',
            'reference_type' => Product::class,
            'reference_id' => $product->getKey(),
            'quantity_delta' => (string) $delta,
            'meta' => ['source' => 'product_form'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncBatchLocations(Product $product, array $data): void
    {
        if (! array_key_exists('batch_locations', $data)) {
            return;
        }

        foreach ($data['batch_locations'] as $row) {
            $batchId = (int) ($row['id'] ?? 0);
            if ($batchId <= 0) {
                continue;
            }

            ProductBatch::query()
                ->where('product_id', $product->getKey())
                ->whereKey($batchId)
                ->update([
                    'storage_location_id' => $row['storage_location_id'] ?? null,
                ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $units
     * @return array<int, array<string, mixed>>
     */
    private function normalizeOptionalString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return trim((string) $value);
    }

    private function normalizeOptionalDecimal(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $n = (float) $value;

        return $n >= 0 ? number_format($n, 4, '.', '') : null;
    }

    private function normalizePiecesPerStrip(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $n = (float) $value;

        return $n > 0 ? number_format($n, 4, '.', '') : null;
    }

    private function normalizeStripsPerBox(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $n = (float) $value;

        return $n > 0 ? number_format($n, 4, '.', '') : null;
    }

    private function normalizeBoxesPerCarton(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $n = (float) $value;

        return $n > 0 ? number_format($n, 4, '.', '') : null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $units
     * @return array<int, array<string, mixed>>
     */
    private function normalizeUnitsPayload(
        array $units,
        string $baseUnit,
        ?float $piecesPerStrip = null,
        ?float $stripsPerBox = null,
        ?float $boxesPerCarton = null,
    ): array {
        if ($units === []) {
            throw ValidationException::withMessages([
                'units' => [__('catalog.units_required')],
            ]);
        }

        if ($piecesPerStrip !== null && $piecesPerStrip > 0) {
            $units = $this->applyPiecesPerStrip($units, $baseUnit, $piecesPerStrip);
        }

        if ($stripsPerBox !== null && $stripsPerBox > 0) {
            $units = $this->applyStripsPerBox($units, $baseUnit, $stripsPerBox);
        }

        if ($boxesPerCarton !== null && $boxesPerCarton > 0) {
            $units = $this->applyBoxesPerCarton($units, $boxesPerCarton);
        }

        $normalized = [];
        $defaultCount = 0;

        foreach ($units as $row) {
            $sellUnit = (string) ($row['sell_unit'] ?? '');
            if (! in_array($sellUnit, ProductCatalogOptions::sellUnits(), true)) {
                continue;
            }

            $isDefault = ! empty($row['is_default']);
            if ($isDefault) {
                $defaultCount++;
            }

            $factor = $sellUnit === $baseUnit
                ? 1
                : max(0.0001, (float) ($row['conversion_factor'] ?? 1));

            $normalized[] = [
                'sell_unit' => $sellUnit,
                'conversion_factor' => $factor,
                'purchase_price' => $row['purchase_price'] ?? 0,
                'sale_price' => $row['sale_price'] ?? 0,
                'is_default' => $isDefault,
            ];
        }

        if ($normalized === []) {
            throw ValidationException::withMessages([
                'units' => [__('catalog.units_required')],
            ]);
        }

        if ($defaultCount === 0) {
            foreach ($normalized as $i => $row) {
                if ($row['sell_unit'] === $baseUnit) {
                    $normalized[$i]['is_default'] = true;
                    $defaultCount = 1;
                    break;
                }
            }
            if ($defaultCount === 0) {
                $normalized[0]['is_default'] = true;
            }
        }

        return $normalized;
    }

    /**
     * @param  array<int, array<string, mixed>>  $units
     * @return array<int, array<string, mixed>>
     */
    private function applyPiecesPerStrip(array $units, string $baseUnit, float $piecesPerStrip): array
    {
        $piecesPerStrip = max(0.0001, $piecesPerStrip);

        if ($baseUnit === 'strip') {
            $units = $this->upsertUnitRow($units, 'piece', 1 / $piecesPerStrip);
        } elseif ($baseUnit === 'piece') {
            $units = $this->upsertUnitRow($units, 'strip', $piecesPerStrip);
        }

        return $units;
    }

    /**
     * @param  array<int, array<string, mixed>>  $units
     * @return array<int, array<string, mixed>>
     */
    private function applyStripsPerBox(array $units, string $baseUnit, float $stripsPerBox): array
    {
        if ($baseUnit !== 'strip') {
            return $units;
        }

        return $this->upsertUnitRow($units, 'box', max(0.0001, $stripsPerBox));
    }

    /**
     * @param  array<int, array<string, mixed>>  $units
     * @return array<int, array<string, mixed>>
     */
    private function applyBoxesPerCarton(array $units, float $boxesPerCarton): array
    {
        $boxesPerCarton = max(0.0001, $boxesPerCarton);
        $boxFactor = null;

        foreach ($units as $row) {
            if (($row['sell_unit'] ?? '') === 'box') {
                $boxFactor = max(0.0001, (float) ($row['conversion_factor'] ?? 1));
                break;
            }
        }

        if ($boxFactor === null) {
            return $units;
        }

        return $this->upsertUnitRow($units, 'carton', $boxesPerCarton * $boxFactor);
    }

    /**
     * @param  array<int, array<string, mixed>>  $units
     * @return array<int, array<string, mixed>>
     */
    private function upsertUnitRow(array $units, string $sellUnit, float $conversionFactor): array
    {
        foreach ($units as $i => $row) {
            if (($row['sell_unit'] ?? '') === $sellUnit) {
                $units[$i]['conversion_factor'] = $conversionFactor;

                return $units;
            }
        }

        $units[] = [
            'sell_unit' => $sellUnit,
            'conversion_factor' => $conversionFactor,
            'purchase_price' => 0,
            'sale_price' => 0,
            'is_default' => false,
        ];

        return $units;
    }
}
