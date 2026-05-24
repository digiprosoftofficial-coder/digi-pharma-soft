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

    /**
     * @return list<array{slug: string, name: string, icon_url: string|null}>
     */
    public static function productTypeOptions(): array
    {
        return CatalogProductType::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (CatalogProductType $type) => [
                'slug' => $type->slug,
                'name' => $type->name,
                'icon_url' => ProductTypeIconResolver::urlForTenantType($type),
            ])
            ->values()
            ->all();
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
