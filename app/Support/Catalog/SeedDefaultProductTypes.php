<?php

namespace App\Support\Catalog;

use App\Domain\Catalog\Models\CatalogProductType;
use Illuminate\Support\Facades\DB;

final class SeedDefaultProductTypes
{
    public static function forTenant(int $tenantId): void
    {
        if (CatalogProductType::query()->withoutGlobalScopes()->where('tenant_id', $tenantId)->exists()) {
            return;
        }

        $order = 0;
        foreach (ProductType::cases() as $case) {
            $name = ucfirst(str_replace('_', ' ', $case->value));
            if ($case === ProductType::Other) {
                $name = 'Other';
            }

            DB::table('product_types')->insert([
                'tenant_id' => $tenantId,
                'name' => $name,
                'slug' => $case->value,
                'sort_order' => $order++,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
