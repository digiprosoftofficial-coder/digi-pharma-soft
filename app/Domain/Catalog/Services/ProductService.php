<?php

namespace App\Domain\Catalog\Services;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductBatch;
use App\Domain\Catalog\Repositories\ProductRepository;
use App\Support\Catalog\ProductCatalogOptions;
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
            $units = $this->normalizeUnitsPayload($data['units'] ?? [], $baseUnit);

            $default = collect($units)->firstWhere('is_default', true) ?? $units[0];

            $product = $this->products->store([
                'category_id' => $data['category_id'] ?? null,
                'manufacturer_id' => $data['manufacturer_id'] ?? null,
                'name' => $data['name'],
                'sku' => $sku,
                'barcode' => $barcode,
                'product_type' => $data['product_type'] ?? 'other',
                'base_unit' => $baseUnit,
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
            $units = isset($data['units'])
                ? $this->normalizeUnitsPayload($data['units'], $baseUnit)
                : null;

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
                'unit' => $default['sell_unit'] ?? $product->unit,
                'purchase_price' => $default['purchase_price'] ?? $product->purchase_price,
                'sale_price' => $default['sale_price'] ?? $product->sale_price,
                'min_stock' => $data['min_stock'] ?? $product->min_stock,
                'is_active' => $data['is_active'] ?? $product->is_active,
            ]);

            if ($units !== null) {
                ProductUnitResolver::syncProductUnits($product->fresh(), $units);
            }

            return $product->fresh(['units']);
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $units
     * @return array<int, array<string, mixed>>
     */
    private function normalizeUnitsPayload(array $units, string $baseUnit): array
    {
        if ($units === []) {
            throw ValidationException::withMessages([
                'units' => [__('catalog.units_required')],
            ]);
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
}
