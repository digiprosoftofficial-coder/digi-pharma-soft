<?php

namespace App\Domain\Catalog\Services;

use App\Domain\Catalog\Models\Manufacturer;
use App\Domain\Catalog\Models\MasterProduct;
use App\Domain\Catalog\Models\Product;
use App\Support\Catalog\ProductSkuGenerator;
use App\Support\Catalog\ProductUnitResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Materializes a shared master-catalog entry into a tenant's own product
 * ("lazy activation"). Idempotent: activating the same master product again
 * for the same tenant returns the already-created product instead of duplicating.
 */
final class MasterProductActivationService
{
    public function activate(MasterProduct $master, int $tenantId): Product
    {
        $existing = $this->findExisting($master, $tenantId);
        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($master, $tenantId) {
            // Re-check inside the transaction to avoid a race creating duplicates.
            $existing = $this->findExisting($master, $tenantId);
            if ($existing) {
                return $existing;
            }

            $master->loadMissing('units');

            $manufacturerId = null;
            if (filled($master->manufacturer_name)) {
                $manufacturer = Manufacturer::query()->firstOrCreate(
                    ['tenant_id' => $tenantId, 'name' => $master->manufacturer_name],
                );
                $manufacturerId = $manufacturer->getKey();
            }

            $baseUnit = $master->base_unit ?: 'strip';
            $units = $this->buildUnits($master, $baseUnit);
            $default = collect($units)->firstWhere('is_default', true) ?? $units[0];

            $product = Product::query()->create([
                'tenant_id' => $tenantId,
                'master_product_id' => $master->getKey(),
                'manufacturer_id' => $manufacturerId,
                'name' => $master->name,
                'generic_name' => $master->generic_name,
                'strength' => $master->strength,
                'sku' => $this->uniqueSku($tenantId, $master->sku),
                'barcode' => $this->uniqueBarcode($tenantId, $master->barcode),
                'product_type' => $master->product_type ?: 'other',
                'base_unit' => $baseUnit,
                'pieces_per_strip' => $master->pieces_per_strip,
                'strips_per_box' => $master->strips_per_box,
                'boxes_per_carton' => $master->boxes_per_carton,
                'unit' => $default['sell_unit'],
                'purchase_price' => $default['purchase_price'],
                'sale_price' => $default['sale_price'],
                'min_stock' => 0,
                'is_active' => true,
            ]);

            ProductUnitResolver::syncProductUnits($product, $units);

            return $product->fresh(['units']);
        });
    }

    private function findExisting(MasterProduct $master, int $tenantId): ?Product
    {
        $byMaster = Product::query()
            ->where('tenant_id', $tenantId)
            ->where('master_product_id', $master->getKey())
            ->first();

        if ($byMaster) {
            return $byMaster;
        }

        // Link an existing product that matches the master barcode so we never
        // create a duplicate of a medicine the pharmacy already entered manually.
        if (filled($master->barcode)) {
            $byBarcode = Product::query()
                ->where('tenant_id', $tenantId)
                ->where('barcode', $master->barcode)
                ->first();

            if ($byBarcode) {
                if ($byBarcode->master_product_id === null) {
                    $byBarcode->forceFill(['master_product_id' => $master->getKey()])->save();
                }

                return $byBarcode;
            }
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildUnits(MasterProduct $master, string $baseUnit): array
    {
        if ($master->units->isNotEmpty()) {
            $hasDefault = $master->units->contains(fn ($u) => (bool) $u->is_default);

            return $master->units->values()->map(fn ($u, $index) => [
                'sell_unit' => $u->sell_unit,
                'conversion_factor' => (float) $u->conversion_factor,
                'purchase_price' => (float) $u->purchase_price,
                'sale_price' => (float) $u->sale_price,
                'is_default' => $hasDefault ? (bool) $u->is_default : $index === 0,
            ])->all();
        }

        return [[
            'sell_unit' => $baseUnit,
            'conversion_factor' => 1,
            'purchase_price' => (float) $master->default_purchase_price,
            'sale_price' => (float) $master->mrp,
            'is_default' => true,
        ]];
    }

    private function uniqueSku(int $tenantId, ?string $baseSku): string
    {
        $base = filled($baseSku) ? Str::upper($baseSku) : ProductSkuGenerator::generate($tenantId);
        $candidate = $base;
        $suffix = 1;

        while (Product::query()
            ->where('tenant_id', $tenantId)
            ->where('sku', $candidate)
            ->exists()) {
            $candidate = $base.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function uniqueBarcode(int $tenantId, ?string $baseBarcode): string
    {
        if (blank($baseBarcode)) {
            return 'BC-'.Str::upper(Str::random(10));
        }

        $candidate = $baseBarcode;
        $suffix = 1;

        while (Product::query()
            ->where('tenant_id', $tenantId)
            ->where('barcode', $candidate)
            ->exists()) {
            $candidate = $baseBarcode.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }
}
