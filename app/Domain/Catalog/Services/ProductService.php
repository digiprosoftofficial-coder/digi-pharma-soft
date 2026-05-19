<?php

namespace App\Domain\Catalog\Services;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductBatch;
use App\Domain\Catalog\Repositories\ProductRepository;
use App\Domain\Inventory\Models\StockMovement;
use App\Support\Catalog\ProductCatalogOptions;
use App\Support\Catalog\ProductStockCalculator;
use App\Support\Catalog\ProductUnitResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ProductService
{
    public function __construct(private readonly ProductRepository $products) {}

    public function createProduct(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $sku = $data['sku'];
            $barcode = $data['barcode'] ?? null;
            if ($barcode === null || $barcode === '') {
                $barcode = 'BC-'.Str::upper(Str::slug($sku, '')).'-'.Str::upper(Str::random(4));
            }

            $baseUnit = $data['base_unit'] ?? 'strip';
            $piecesPerStrip = isset($data['pieces_per_strip']) ? (float) $data['pieces_per_strip'] : null;
            $boxesPerCarton = isset($data['boxes_per_carton']) ? (float) $data['boxes_per_carton'] : null;
            $units = $this->normalizeUnitsPayload(
                $data['units'] ?? [],
                $baseUnit,
                $piecesPerStrip,
                $boxesPerCarton,
            );

            $default = collect($units)->firstWhere('is_default', true) ?? $units[0];

            $product = $this->products->store([
                'category_id' => $data['category_id'] ?? null,
                'manufacturer_id' => $data['manufacturer_id'] ?? null,
                'name' => $data['name'],
                'sku' => $sku,
                'barcode' => $barcode,
                'product_type' => $data['product_type'] ?? 'other',
                'base_unit' => $baseUnit,
                'pieces_per_strip' => $this->normalizePiecesPerStrip($data['pieces_per_strip'] ?? null),
                'boxes_per_carton' => $this->normalizeBoxesPerCarton($data['boxes_per_carton'] ?? null),
                'unit' => $default['sell_unit'],
                'purchase_price' => $default['purchase_price'],
                'sale_price' => $default['sale_price'],
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

        ProductBatch::query()->create([
            'product_id' => $product->getKey(),
            'batch_no' => $batchNo,
            'expiry_date' => $data['opening_expiry_date'] ?? null,
            'quantity_on_hand' => $quantity,
            'purchase_unit_cost' => $defaultUnit['purchase_price'] ?? 0,
        ]);
    }

    public function updateProduct(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $baseUnit = $data['base_unit'] ?? $product->base_unit;
            $piecesPerStrip = array_key_exists('pieces_per_strip', $data)
                ? (isset($data['pieces_per_strip']) ? (float) $data['pieces_per_strip'] : null)
                : null;
            $boxesPerCarton = array_key_exists('boxes_per_carton', $data)
                ? (isset($data['boxes_per_carton']) ? (float) $data['boxes_per_carton'] : null)
                : null;

            if (isset($data['units'])) {
                $units = $this->normalizeUnitsPayload($data['units'], $baseUnit, $piecesPerStrip, $boxesPerCarton);
            } elseif (($piecesPerStrip !== null && $piecesPerStrip > 0) || ($boxesPerCarton !== null && $boxesPerCarton > 0)) {
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
                    $boxesPerCarton ?? ($product->boxes_per_carton !== null ? (float) $product->boxes_per_carton : null),
                );
            } else {
                $units = null;
            }

            $default = $units
                ? (collect($units)->firstWhere('is_default', true) ?? $units[0])
                : null;

            $this->products->update($product, [
                'category_id' => $data['category_id'] ?? $product->category_id,
                'manufacturer_id' => $data['manufacturer_id'] ?? $product->manufacturer_id,
                'name' => $data['name'] ?? $product->name,
                'sku' => $data['sku'] ?? $product->sku,
                'barcode' => array_key_exists('barcode', $data) ? $data['barcode'] : $product->barcode,
                'product_type' => $data['product_type'] ?? $product->product_type,
                'base_unit' => $baseUnit,
                'pieces_per_strip' => array_key_exists('pieces_per_strip', $data)
                    ? $this->normalizePiecesPerStrip($data['pieces_per_strip'])
                    : $product->pieces_per_strip,
                'boxes_per_carton' => array_key_exists('boxes_per_carton', $data)
                    ? $this->normalizeBoxesPerCarton($data['boxes_per_carton'])
                    : $product->boxes_per_carton,
                'unit' => $default['sell_unit'] ?? $product->unit,
                'purchase_price' => $default['purchase_price'] ?? $product->purchase_price,
                'sale_price' => $default['sale_price'] ?? $product->sale_price,
                'min_stock' => $data['min_stock'] ?? $product->min_stock,
                'is_active' => $data['is_active'] ?? $product->is_active,
            ]);

            if ($units !== null) {
                ProductUnitResolver::syncProductUnits($product->fresh(), $units);
            }

            $this->applyStockAdjustmentIfProvided($product->fresh(['units', 'batches']), $data);

            return $product->fresh(['units', 'batches']);
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
     * @param  array<int, array<string, mixed>>  $units
     * @return array<int, array<string, mixed>>
     */
    private function normalizePiecesPerStrip(mixed $value): ?string
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
