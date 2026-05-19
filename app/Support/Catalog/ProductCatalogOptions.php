<?php

namespace App\Support\Catalog;

use App\Domain\Catalog\Models\CatalogProductType;
use Illuminate\Validation\Rule;

final class ProductCatalogOptions
{
    /**
     * @return list<string>
     */
    public static function productTypes(): array
    {
        $fromDb = CatalogProductType::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('slug')
            ->all();

        if ($fromDb !== []) {
            return array_values($fromDb);
        }

        return ProductType::values();
    }

    /**
     * @return list<string>
     */
    public static function sellUnits(): array
    {
        return SellUnit::values();
    }

    public static function productTypeRule(): \Illuminate\Validation\Rules\In
    {
        return Rule::in(self::productTypes());
    }

    public static function sellUnitRule(): \Illuminate\Validation\Rules\In
    {
        return Rule::in(self::sellUnits());
    }
}
