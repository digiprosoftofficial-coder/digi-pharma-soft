<?php

namespace App\Support\Catalog;

use App\Domain\Catalog\Models\Product;

final class ProductStockCalculator
{
    public static function piecesPerStrip(Product $product): ?float
    {
        if ($product->pieces_per_strip !== null && (float) $product->pieces_per_strip > 0) {
            return (float) $product->pieces_per_strip;
        }

        $product->loadMissing('units');
        $baseUnit = $product->base_unit ?? 'strip';

        if ($baseUnit === 'strip') {
            $pieceUnit = $product->units->firstWhere('sell_unit', 'piece');
            if ($pieceUnit && (float) $pieceUnit->conversion_factor > 0) {
                return 1 / (float) $pieceUnit->conversion_factor;
            }
        }

        if ($baseUnit === 'piece') {
            $stripUnit = $product->units->firstWhere('sell_unit', 'strip');
            if ($stripUnit && (float) $stripUnit->conversion_factor > 0) {
                return (float) $stripUnit->conversion_factor;
            }
        }

        return null;
    }

    public static function totalPieces(Product $product, float|string $baseStock): ?float
    {
        $piecesPerStrip = self::piecesPerStrip($product);
        if ($piecesPerStrip === null) {
            return null;
        }

        $stock = (float) $baseStock;
        $baseUnit = $product->base_unit ?? 'strip';

        return match ($baseUnit) {
            'piece' => $stock,
            'strip' => $stock * $piecesPerStrip,
            default => null,
        };
    }

    public static function quantityInSellUnit(
        Product $product,
        float|string $baseStock,
        string $sellUnit,
        ?float $conversionFactor = null,
    ): float {
        $stock = (float) $baseStock;
        $baseUnit = $product->base_unit ?? 'strip';

        if ($sellUnit === $baseUnit) {
            return $stock;
        }

        if ($sellUnit === 'piece') {
            $totalPieces = self::totalPieces($product, $stock);
            if ($totalPieces !== null) {
                return $totalPieces;
            }
        }

        $factor = $conversionFactor;
        if ($factor === null || $factor <= 0) {
            $product->loadMissing('units');
            $unit = $product->units->firstWhere('sell_unit', $sellUnit);
            $factor = $unit ? (float) $unit->conversion_factor : 1.0;
        }

        return $stock / max(0.0001, $factor);
    }

    public static function formatQuantity(float $qty): string
    {
        return $qty % 1 === 0.0
            ? (string) (int) $qty
            : number_format($qty, 2, '.', '');
    }
}
