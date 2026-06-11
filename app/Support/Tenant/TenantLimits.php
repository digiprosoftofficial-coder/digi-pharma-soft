<?php

namespace App\Support\Tenant;

use App\Domain\Catalog\Models\Product;
use App\Domain\Tenant\Models\Branch;
use App\Domain\Tenant\Models\Tenant;

/**
 * Numeric usage limits driven by the tenant's subscription plan.
 *
 * A null (or non-positive) value means "unlimited". A tenant-level override in
 * settings['limits'] takes precedence over the plan value, mirroring TenantFeatures.
 */
final class TenantLimits
{
    public const MAX_PRODUCTS = 'max_products';

    public const MAX_IMPORT_ROWS = 'max_import_rows';

    public const MAX_BRANCHES = 'max_branches';

    public static function maxProducts(?Tenant $tenant): ?int
    {
        return self::limit($tenant, self::MAX_PRODUCTS);
    }

    public static function maxImportRows(?Tenant $tenant): ?int
    {
        return self::limit($tenant, self::MAX_IMPORT_ROWS);
    }

    public static function maxBranches(?Tenant $tenant): ?int
    {
        return self::limit($tenant, self::MAX_BRANCHES);
    }

    public static function remainingBranches(?Tenant $tenant): ?int
    {
        $max = self::maxBranches($tenant);
        if ($max === null) {
            return null;
        }

        return max(0, $max - Branch::query()->count());
    }

    public static function limit(?Tenant $tenant, string $key): ?int
    {
        if ($tenant === null) {
            return null;
        }

        $override = $tenant->settings['limits'][$key] ?? null;
        if ($override !== null && $override !== '') {
            return self::normalize($override);
        }

        $tenant->loadMissing('activeSubscription.plan');
        $planLimits = $tenant->activeSubscription?->plan?->limits ?? [];

        return self::normalize($planLimits[$key] ?? null);
    }

    /**
     * Number of additional products the tenant may still create (null = unlimited).
     */
    public static function remainingProducts(?Tenant $tenant): ?int
    {
        $max = self::maxProducts($tenant);
        if ($max === null) {
            return null;
        }

        return max(0, $max - Product::query()->count());
    }

    public static function productLimitReached(?Tenant $tenant): bool
    {
        $remaining = self::remainingProducts($tenant);

        return $remaining !== null && $remaining <= 0;
    }

    /**
     * @return 'inherit'|int  The override value or 'inherit' if using plan default.
     */
    public static function maxProductsOverrideMode(Tenant $tenant): string|int
    {
        return self::overrideMode($tenant, self::MAX_PRODUCTS);
    }

    /**
     * @return 'inherit'|int  The override value or 'inherit' if using plan default.
     */
    public static function maxImportRowsOverrideMode(Tenant $tenant): string|int
    {
        return self::overrideMode($tenant, self::MAX_IMPORT_ROWS);
    }

    /**
     * @return 'inherit'|int
     */
    public static function maxBranchesOverrideMode(Tenant $tenant): string|int
    {
        return self::overrideMode($tenant, self::MAX_BRANCHES);
    }

    /**
     * @return 'inherit'|int
     */
    private static function overrideMode(Tenant $tenant, string $key): string|int
    {
        $override = $tenant->settings['limits'][$key] ?? null;

        if ($override === null || $override === '' || $override === 'inherit') {
            return 'inherit';
        }

        return self::normalize($override) ?? 'inherit';
    }

    /**
     * Plan default for a limit (null = unlimited).
     */
    public static function fromPlanLimit(Tenant $tenant, string $key): ?int
    {
        $tenant->loadMissing('activeSubscription.plan');
        $planLimits = $tenant->activeSubscription?->plan?->limits ?? [];

        return self::normalize($planLimits[$key] ?? null);
    }

    private static function normalize(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $int = (int) $value;

        return $int > 0 ? $int : null;
    }
}
