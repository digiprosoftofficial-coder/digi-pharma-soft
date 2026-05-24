<?php

namespace App\Support\Catalog;

use App\Domain\Catalog\Models\CatalogProductType;
use App\Domain\Platform\Models\PlatformProductType;
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

            $iconPath = self::initialIconPathForSlug($case->value, $tenantId);

            DB::table('product_types')->insert([
                'tenant_id' => $tenantId,
                'name' => $name,
                'slug' => $case->value,
                'sort_order' => $order++,
                'icon_path' => $iconPath,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Copy platform default icons into tenant rows that have no custom icon yet.
     */
    public static function syncIconsFromPlatform(int $tenantId): void
    {
        $types = CatalogProductType::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->get();

        foreach ($types as $type) {
            if ($type->icon_path) {
                continue;
            }

            $iconPath = self::initialIconPathForSlug($type->slug, $tenantId);
            if ($iconPath === null) {
                continue;
            }

            $type->update(['icon_path' => $iconPath]);
        }
    }

    private static function initialIconPathForSlug(string $slug, int $tenantId): ?string
    {
        $platform = PlatformProductType::query()->where('slug', $slug)->first();
        if ($platform?->icon_path === null) {
            return null;
        }

        return ProductTypeIconStorage::copyFromPath($platform->icon_path, $tenantId, $slug);
    }
}
