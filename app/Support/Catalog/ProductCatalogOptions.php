<?php

namespace App\Support\Catalog;

use Illuminate\Validation\Rule;

final class ProductCatalogOptions
{
    /**
     * @return list<string>
     */
    public static function productTypes(): array
    {
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
