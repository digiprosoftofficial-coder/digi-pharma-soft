<?php

namespace App\Support\Catalog;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductBatch;
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

    public static function resolveConversionFactor(
        Product $product,
        string $sellUnit,
        ?float $override = null,
    ): float {
        $baseUnit = $product->base_unit ?? 'strip';

        if ($sellUnit === $baseUnit) {
            return 1.0;
        }

        if ($override !== null && $override > 0) {
            return max(0.0001, $override);
        }

        $product->loadMissing('units');
        $unit = $product->units->firstWhere('sell_unit', $sellUnit);
        if ($unit) {
            return max(0.0001, (float) $unit->conversion_factor);
        }

        $synthesized = self::synthesizedConversionFactor($product, $sellUnit);
        if ($synthesized !== null) {
            return $synthesized;
        }

        throw new InvalidArgumentException("Sell unit [{$sellUnit}] is not configured for this product.");
    }

    /**
     * Derive conversion from catalog pack fields when a ProductUnit row is missing
     * (e.g. sell by piece using pieces_per_strip without a dedicated unit row).
     */
    public static function synthesizedConversionFactor(Product $product, string $sellUnit): ?float
    {
        $baseUnit = $product->base_unit ?? 'strip';
        if ($sellUnit === $baseUnit) {
            return 1.0;
        }

        $piecesPerStrip = $product->pieces_per_strip !== null ? (float) $product->pieces_per_strip : 0.0;
        $stripsPerBox = $product->strips_per_box !== null ? (float) $product->strips_per_box : 0.0;
        $piecesPerBox = $product->pieces_per_box !== null ? (float) $product->pieces_per_box : 0.0;
        $boxesPerCarton = $product->boxes_per_carton !== null ? (float) $product->boxes_per_carton : 0.0;

        if ($sellUnit === 'piece' && $baseUnit === 'strip' && $piecesPerStrip > 0) {
            return max(0.0001, 1 / $piecesPerStrip);
        }

        if ($sellUnit === 'strip' && $baseUnit === 'piece' && $piecesPerStrip > 0) {
            return max(0.0001, $piecesPerStrip);
        }

        if ($sellUnit === 'box' && $baseUnit === 'strip' && $stripsPerBox > 0) {
            return max(0.0001, $stripsPerBox);
        }

        if ($sellUnit === 'box' && $baseUnit === 'piece' && $piecesPerBox > 0) {
            return max(0.0001, $piecesPerBox);
        }

        if ($sellUnit === 'carton' && $baseUnit === 'box' && $boxesPerCarton > 0) {
            return max(0.0001, $boxesPerCarton);
        }

        if ($sellUnit === 'carton' && $baseUnit === 'strip' && $stripsPerBox > 0 && $boxesPerCarton > 0) {
            return max(0.0001, $stripsPerBox * $boxesPerCarton);
        }

        if ($sellUnit === 'carton' && $baseUnit === 'piece' && $piecesPerBox > 0 && $boxesPerCarton > 0) {
            return max(0.0001, $piecesPerBox * $boxesPerCarton);
        }

        return null;
    }

    public static function conversionFactorForBatch(
        Product $product,
        ProductBatch $batch,
        string $sellUnit,
        ?float $override = null,
    ): float {
        if ($override !== null && $override > 0) {
            return max(0.0001, $override);
        }

        if (
            $batch->pack_sell_unit === $sellUnit
            && $batch->pack_conversion_factor !== null
            && (float) $batch->pack_conversion_factor > 0
        ) {
            return (float) $batch->pack_conversion_factor;
        }

        return self::resolveConversionFactor($product, $sellUnit);
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
