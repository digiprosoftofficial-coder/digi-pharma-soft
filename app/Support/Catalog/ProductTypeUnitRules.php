<?php

namespace App\Support\Catalog;

/**
 * Which sell/base units apply per product type (e.g. strip only for tablets/capsules).
 */
final class ProductTypeUnitRules
{
    /** Product types that use strip as the typical base unit. @var list<string> */
    public const STRIP_PRODUCT_TYPES = [
        'tablet',
        'capsule',
    ];

    public static function usesStripUnit(string $productType): bool
    {
        return in_array(strtolower($productType), self::STRIP_PRODUCT_TYPES, true);
    }

    public static function defaultBaseUnit(string $productType): string
    {
        return self::usesStripUnit($productType) ? SellUnit::Strip->value : SellUnit::Piece->value;
    }

    /**
     * Sell units allowed for a product type. When editing, pass units already on the product
     * so legacy strip-based rows remain selectable.
     *
     * @param  list<string>|null  $includeUnits
     * @return list<string>
     */
    public static function sellUnitsFor(string $productType, ?array $includeUnits = null): array
    {
        $all = SellUnit::values();

        if (self::usesStripUnit($productType)) {
            $allowed = $all;
        } else {
            $allowed = array_values(array_filter($all, static fn (string $u) => $u !== SellUnit::Strip->value));
        }

        if ($includeUnits !== null) {
            foreach ($includeUnits as $unit) {
                if ($unit !== '' && in_array($unit, $all, true) && ! in_array($unit, $allowed, true)) {
                    $allowed[] = $unit;
                }
            }
        }

        return self::orderUnits($allowed);
    }

    public static function baseUnitAllowed(string $productType, string $baseUnit): bool
    {
        return in_array($baseUnit, self::sellUnitsFor($productType, [$baseUnit]), true);
    }

    /**
     * @param  list<string>  $units
     * @return list<string>
     */
    private static function orderUnits(array $units): array
    {
        $order = array_flip(SellUnit::values());

        usort($units, static fn (string $a, string $b) => ($order[$a] ?? 99) <=> ($order[$b] ?? 99));

        return array_values($units);
    }
}
