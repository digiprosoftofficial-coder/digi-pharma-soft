<?php

namespace App\Support\Tenant;

use App\Domain\Tenant\Models\Tenant;
use Illuminate\Support\Collection;

final class TenantPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function listItem(Tenant $tenant): array
    {
        return [
            'id' => $tenant->getKey(),
            'name' => $tenant->name,
            'slug' => $tenant->slug,
            'is_active' => $tenant->is_active,
            'trial_ends_at' => $tenant->trial_ends_at?->toIso8601String(),
            'subscription_ends_at' => $tenant->subscription_ends_at?->toIso8601String(),
            'suspended_at' => $tenant->suspended_at?->toIso8601String(),
            'users_count' => $tenant->users_count ?? $tenant->users()->count(),
            'status' => TenantStatus::resolve($tenant),
            'plan_name' => $tenant->activeSubscription?->plan?->name,
            'reseller_id' => $tenant->reseller_id,
            'reseller_name' => $tenant->reseller?->name,
            'created_at' => $tenant->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function detail(Tenant $tenant): array
    {
        return [
            ...self::listItem($tenant),
            'settings' => $tenant->settings ?? [],
            'wholesale_pricing_override' => TenantFeatures::wholesalePricingOverrideMode($tenant),
            'wholesale_pricing_enabled' => TenantFeatures::wholesalePricingEnabled($tenant),
            'plan_wholesale_pricing' => TenantFeatures::fromPlan($tenant, TenantFeatures::WHOLESALE_PRICING),
            'max_products_override' => TenantLimits::maxProductsOverrideMode($tenant),
            'max_products_effective' => TenantLimits::maxProducts($tenant),
            'plan_max_products' => TenantLimits::fromPlanLimit($tenant, TenantLimits::MAX_PRODUCTS),
            'max_import_rows_override' => TenantLimits::maxImportRowsOverrideMode($tenant),
            'max_import_rows_effective' => TenantLimits::maxImportRows($tenant),
            'plan_max_import_rows' => TenantLimits::fromPlanLimit($tenant, TenantLimits::MAX_IMPORT_ROWS),
            'internal_notes' => $tenant->internal_notes,
            'users' => $tenant->relationLoaded('users')
                ? $tenant->users->map(fn ($u) => [
                    'id' => $u->getKey(),
                    'name' => $u->name,
                    'email' => $u->email,
                    'email_verified_at' => $u->email_verified_at?->toIso8601String(),
                    'last_login_at' => $u->last_login_at?->toIso8601String(),
                    'invite_pending' => $u->email_verified_at === null,
                ])
                : [],
            'subscription_history' => $tenant->relationLoaded('subscriptions')
                ? $tenant->subscriptions->map(fn ($sub) => [
                    'id' => $sub->getKey(),
                    'plan_name' => $sub->plan?->name,
                    'status' => $sub->status,
                    'starts_at' => $sub->starts_at?->toIso8601String(),
                    'ends_at' => $sub->ends_at?->toIso8601String(),
                ])->values()->all()
                : [],
            'subscription' => $tenant->activeSubscription ? [
                'id' => $tenant->activeSubscription->getKey(),
                'plan_id' => $tenant->activeSubscription->subscription_plan_id,
                'plan_name' => $tenant->activeSubscription->plan?->name,
                'starts_at' => $tenant->activeSubscription->starts_at?->toIso8601String(),
                'ends_at' => $tenant->activeSubscription->ends_at?->toIso8601String(),
                'status' => $tenant->activeSubscription->status,
            ] : null,
        ];
    }

    /**
     * @param  Collection<int, Tenant>  $tenants
     * @return list<array<string, mixed>>
     */
    public static function collection(Collection $tenants): array
    {
        return $tenants->map(fn (Tenant $t) => self::listItem($t))->values()->all();
    }
}
