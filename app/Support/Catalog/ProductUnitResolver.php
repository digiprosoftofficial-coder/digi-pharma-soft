<?php

namespace App\Support\Catalog;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductUnit;
use InvalidArgumentException;

final class ProductUnitResolver
{
    public static function forProduct(Product $product, string $sellUnit): ProductUnit
    {
        $product->loadMissing('units');

        $unit = $product->units->firstWhere('sell_unit', $sellUnit);

        if (! $unit) {
            throw new InvalidArgumentException("Sell unit [{$sellUnit}] is not configured for this product.");
        }

        return $unit;
    }

    public static function quantityBase(float|string $quantity, float|string $conversionFactor): string
    {
        $base = (float) $quantity * (float) $conversionFactor;

        return number_format($base, 4, '.', '');
    }

    /**
     * @param  array<int, array<string, mixed>>  $units
     */
    public static function syncProductUnits(Product $product, array $units): void
    {
        $product->units()->delete();

        $defaultAssigned = false;

        foreach ($units as $index => $row) {
            $sellUnit = (string) $row['sell_unit'];
            $isDefault = ! empty($row['is_default']);
            if ($isDefault) {
                $defaultAssigned = true;
            }

            $factor = (float) ($row['conversion_factor'] ?? 1);
            if ($sellUnit === $product->base_unit) {
                $factor = 1;
            }

            $product->units()->create([
                'sell_unit' => $sellUnit,
                'conversion_factor' => max(0.0001, $factor),
                'purchase_price' => $row['purchase_price'] ?? 0,
                'sale_price' => $row['sale_price'] ?? 0,
                'is_default' => $isDefault,
                'sort_order' => $index,
            ]);
        }

        if (! $defaultAssigned && $product->units()->exists()) {
            $product->units()->orderBy('sort_order')->first()?->update(['is_default' => true]);
        }

        $default = $product->units()->where('is_default', true)->first()
            ?? $product->units()->orderBy('sort_order')->first();

        if ($default) {
            $product->update([
                'unit' => $default->sell_unit,
                'purchase_price' => $default->purchase_price,
                'sale_price' => $default->sale_price,
            ]);
        }
    }
}
