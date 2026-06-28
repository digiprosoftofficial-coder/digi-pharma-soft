<?php

namespace App\Support\Tenant;

use App\Domain\Tenant\Models\Tenant;

final class TenantFeatures
{
    public const WHOLESALE_PRICING = 'wholesale_pricing';

    public const MARKUP_PRICING = 'markup_pricing';

    public const BULK_IMPORT = 'bulk_import';

    public const ADVANCED_CATALOG = 'advanced_catalog';

    public const MULTI_BRANCH = 'multi_branch';

    public const SUPPLIER_BRANCH_LEDGER = 'supplier_branch_ledger';

    public const EMPLOYEE_MANAGEMENT = 'employee_management';

    public const ATTENDANCE = 'attendance';

    public const HR_PAYROLL = 'hr_payroll';

    public const BARCODE_CAMERA_SCAN = 'barcode_camera_scan';

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

    public static function markupPricingEnabled(?Tenant $tenant): bool
    {
        return self::enabled($tenant, self::MARKUP_PRICING);
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

    public static function supplierBranchLedgerEnabled(?Tenant $tenant): bool
    {
        if ($tenant === null) {
            return false;
        }

        if (! self::multiBranchEnabled($tenant)) {
            return false;
        }

        return self::enabled($tenant, self::SUPPLIER_BRANCH_LEDGER, false);
    }

    public static function employeeManagementEnabled(?Tenant $tenant): bool
    {
        return self::enabled($tenant, self::EMPLOYEE_MANAGEMENT, true);
    }

    public static function attendanceEnabled(?Tenant $tenant): bool
    {
        return self::enabled($tenant, self::ATTENDANCE, false);
    }

    public static function hrPayrollEnabled(?Tenant $tenant): bool
    {
        return self::enabled($tenant, self::HR_PAYROLL, false);
    }

    public static function barcodeCameraScanEnabled(?Tenant $tenant): bool
    {
        return self::enabled($tenant, self::BARCODE_CAMERA_SCAN, false);
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
    public static function supplierBranchLedgerOverrideMode(Tenant $tenant): string
    {
        return self::overrideMode($tenant, self::SUPPLIER_BRANCH_LEDGER);
    }

    /**
     * @return 'inherit'|'on'|'off'
     */
    public static function employeeManagementOverrideMode(Tenant $tenant): string
    {
        return self::overrideMode($tenant, self::EMPLOYEE_MANAGEMENT);
    }

    /**
     * @return 'inherit'|'on'|'off'
     */
    public static function attendanceOverrideMode(Tenant $tenant): string
    {
        return self::overrideMode($tenant, self::ATTENDANCE);
    }

    /**
     * @return 'inherit'|'on'|'off'
     */
    public static function hrPayrollOverrideMode(Tenant $tenant): string
    {
        return self::overrideMode($tenant, self::HR_PAYROLL);
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
            'markup_pricing' => self::markupPricingEnabled($tenant),
            'bulk_import' => self::bulkImportEnabled($tenant),
            'advanced_catalog' => self::advancedCatalogEnabled($tenant),
            'multi_branch' => self::multiBranchEnabled($tenant),
            'supplier_branch_ledger' => self::supplierBranchLedgerEnabled($tenant),
            'employee_management' => self::employeeManagementEnabled($tenant),
            'attendance' => self::attendanceEnabled($tenant),
            'hr_payroll' => self::hrPayrollEnabled($tenant),
            'barcode_camera_scan' => self::barcodeCameraScanEnabled($tenant),
        ];
    }
}
