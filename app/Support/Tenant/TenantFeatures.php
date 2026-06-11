<?php

namespace App\Support\Tenant;

use App\Domain\Tenant\Models\Tenant;

final class TenantFeatures
{
    public const WHOLESALE_PRICING = 'wholesale_pricing';

    public const BULK_IMPORT = 'bulk_import';

    public const ADVANCED_CATALOG = 'advanced_catalog';

    public const MULTI_BRANCH = 'multi_branch';

    /**
     * Catalog fields only available when the advanced_catalog feature is on.
     *
     * @var list<string>
     */
    public const ADVANCED_CATALOG_FIELDS = [
        'generic_name',
        'strength',
        'vat_percent',
        'short_description',
    ];

    public static function wholesalePricingEnabled(?Tenant $tenant): bool
    {
        return self::enabled($tenant, self::WHOLESALE_PRICING);
    }

    public static function bulkImportEnabled(?Tenant $tenant): bool
    {
        return self::enabled($tenant, self::BULK_IMPORT, true);
    }

    public static function advancedCatalogEnabled(?Tenant $tenant): bool
    {
        return self::enabled($tenant, self::ADVANCED_CATALOG, true);
    }

    public static function multiBranchEnabled(?Tenant $tenant): bool
    {
        return self::enabled($tenant, self::MULTI_BRANCH, false);
    }

    public static function enabled(?Tenant $tenant, string $feature, bool $default = false): bool
    {
        if ($tenant === null) {
            return false;
        }

        $override = self::override($tenant, $feature);
        if ($override !== null) {
            return $override;
        }

        return self::fromPlan($tenant, $feature, $default);
    }

    public static function override(?Tenant $tenant, string $feature): ?bool
    {
        if ($tenant === null) {
            return null;
        }

        $features = $tenant->settings['features'] ?? [];
        if (! array_key_exists($feature, $features)) {
            return null;
        }

        return (bool) $features[$feature];
    }

    public static function fromPlan(Tenant $tenant, string $feature, bool $default = false): bool
    {
        $tenant->loadMissing('activeSubscription.plan');
        $planFeatures = $tenant->activeSubscription?->plan?->features ?? [];

        return (bool) ($planFeatures[$feature] ?? $default);
    }

    /**
     * @return 'inherit'|'on'|'off'
     */
    public static function wholesalePricingOverrideMode(Tenant $tenant): string
    {
        return self::overrideMode($tenant, self::WHOLESALE_PRICING);
    }

    /**
     * @return 'inherit'|'on'|'off'
     */
    public static function multiBranchOverrideMode(Tenant $tenant): string
    {
        return self::overrideMode($tenant, self::MULTI_BRANCH);
    }

    /**
     * @return 'inherit'|'on'|'off'
     */
    private static function overrideMode(Tenant $tenant, string $feature): string
    {
        $override = self::override($tenant, $feature);

        if ($override === null) {
            return 'inherit';
        }

        return $override ? 'on' : 'off';
    }

    /**
     * Get the import preset from the tenant's plan.
     */
    public static function importPreset(?Tenant $tenant): string
    {
        if ($tenant === null) {
            return 'pro';
        }

        $tenant->loadMissing('activeSubscription.plan');
        $planFeatures = $tenant->activeSubscription?->plan?->features ?? [];

        return $planFeatures['import_preset'] ?? 'pro';
    }

    /**
     * Get custom import columns from the tenant's plan (only used when preset is 'custom').
     *
     * @return list<string>|null
     */
    public static function importColumns(?Tenant $tenant): ?array
    {
        if ($tenant === null) {
            return null;
        }

        $tenant->loadMissing('activeSubscription.plan');
        $planFeatures = $tenant->activeSubscription?->plan?->features ?? [];

        $columns = $planFeatures['import_columns'] ?? null;

        return is_array($columns) ? array_values($columns) : null;
    }

    /**
     * @return array<string, bool>
     */
    public static function shareForInertia(?Tenant $tenant): array
    {
        return [
            'wholesale_pricing' => self::wholesalePricingEnabled($tenant),
            'bulk_import' => self::bulkImportEnabled($tenant),
            'advanced_catalog' => self::advancedCatalogEnabled($tenant),
            'multi_branch' => self::multiBranchEnabled($tenant),
        ];
    }
}
