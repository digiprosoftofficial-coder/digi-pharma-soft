<?php

namespace App\Support\Catalog;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductBatch;

final class BatchSalePricing
{
    /**
     * Purchase cost normalized to the product base unit (e.g. per strip).
     */
    public static function costPerBaseUnit(ProductBatch $batch): float
    {
        $cost = (float) $batch->purchase_unit_cost;

        if (
            $batch->pack_sell_unit
            && $batch->pack_conversion_factor !== null
            && (float) $batch->pack_conversion_factor > 0
        ) {
            return $cost / (float) $batch->pack_conversion_factor;
        }

        return $cost;
    }

    /**
     * MRP / sale price from purchase, normalized to the product base unit.
     */
    public static function salePricePerBaseUnit(ProductBatch $batch): ?float
    {
        if ($batch->sale_price === null || $batch->sale_price === '') {
            return null;
        }

        $price = (float) $batch->sale_price;

        if (
            $batch->pack_sell_unit
            && $batch->pack_conversion_factor !== null
            && (float) $batch->pack_conversion_factor > 0
        ) {
            return $price / (float) $batch->pack_conversion_factor;
        }

        return $price;
    }

    public static function batchSalePriceInSellUnit(
        ProductBatch $batch,
        Product $product,
        string $sellUnit,
    ): ?float {
        $basePrice = self::salePricePerBaseUnit($batch);
        if ($basePrice === null) {
            return null;
        }

        $factor = ProductUnitResolver::conversionFactorForBatch($product, $batch, $sellUnit);

        return round($basePrice * $factor, 4);
    }

    public static function unitCostInSellUnit(
        ProductBatch $batch,
        Product $product,
        string $sellUnit,
    ): float {
        $factor = ProductUnitResolver::conversionFactorForBatch($product, $batch, $sellUnit);

        return self::costPerBaseUnit($batch) * $factor;
    }

    public static function resolveMarkupPercent(Product $product, ProductBatch $batch): ?float
    {
        if ($batch->markup_percent !== null) {
            return (float) $batch->markup_percent;
        }

        if ($product->default_markup_percent !== null) {
            return (float) $product->default_markup_percent;
        }

        return null;
    }

    /**
     * Suggested sell price from batch cost + markup. Null when no markup is configured.
     */
    public static function suggestedUnitPrice(
        ProductBatch $batch,
        Product $product,
        string $sellUnit,
    ): ?float {
        $markup = self::resolveMarkupPercent($product, $batch);
        if ($markup === null) {
            return null;
        }

        $unitCost = self::unitCostInSellUnit($batch, $product, $sellUnit);

        return round($unitCost * (1 + ($markup / 100)), 4);
    }

    public static function lineProfit(float $quantity, float $unitPrice, float $unitCostAtSale): float
    {
        return round(($unitPrice - $unitCostAtSale) * $quantity, 4);
    }
}
