<?php

namespace App\Support\Catalog;

use App\Domain\Catalog\Models\CatalogProductType;
use App\Domain\Platform\Models\PlatformProductType;

final class ProductTypeIconResolver
{
    public static function urlForTenantType(CatalogProductType $type): ?string
    {
        if ($type->icon_path) {
            return ProductTypeIconStorage::url($type->icon_path);
        }

        return self::platformUrlForSlug($type->slug);
    }

    public static function urlForSlug(string $slug, ?int $tenantId = null): ?string
    {
        $tenantId ??= tenant_id();

        if ($tenantId) {
            $tenantType = CatalogProductType::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('slug', $slug)
                ->first();

            if ($tenantType?->icon_path) {
                return ProductTypeIconStorage::url($tenantType->icon_path);
            }
        }

        return self::platformUrlForSlug($slug);
    }

    public static function platformUrlForSlug(string $slug): ?string
    {
        $platform = PlatformProductType::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if ($platform?->icon_path) {
            return PlatformProductTypeIconStorage::url($platform->icon_path);
        }

        return null;
    }
}
