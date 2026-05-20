<?php

namespace App\Support\Tenant;

use App\Domain\Tenant\Models\Tenant;

final class TenantFeatures
{
    public const WHOLESALE_PRICING = 'wholesale_pricing';

    public static function wholesalePricingEnabled(?Tenant $tenant): bool
    {
        return self::enabled($tenant, self::WHOLESALE_PRICING);
    }

    public static function enabled(?Tenant $tenant, string $feature): bool
    {
        if ($tenant === null) {
            return false;
        }

        $override = self::override($tenant, $feature);
        if ($override !== null) {
            return $override;
        }

        return self::fromPlan($tenant, $feature);
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

    public static function fromPlan(Tenant $tenant, string $feature): bool
    {
        $tenant->loadMissing('activeSubscription.plan');
        $planFeatures = $tenant->activeSubscription?->plan?->features ?? [];

        return (bool) ($planFeatures[$feature] ?? false);
    }

    /**
     * @return 'inherit'|'on'|'off'
     */
    public static function wholesalePricingOverrideMode(Tenant $tenant): string
    {
        $override = self::override($tenant, self::WHOLESALE_PRICING);

        if ($override === null) {
            return 'inherit';
        }

        return $override ? 'on' : 'off';
    }

    /**
     * @return array<string, bool>
     */
    public static function shareForInertia(?Tenant $tenant): array
    {
        return [
            'wholesale_pricing' => self::wholesalePricingEnabled($tenant),
        ];
    }
}
