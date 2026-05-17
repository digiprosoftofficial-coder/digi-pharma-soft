<?php

namespace App\Support\Tenant;

use App\Domain\Tenant\Models\Tenant;
use Illuminate\Support\Carbon;

final class TenantStatus
{
    public const RUNNING = 'running';

    public const TRIAL = 'trial';

    public const EXPIRING = 'expiring';

    public const EXPIRED = 'expired';

    public const SUSPENDED = 'suspended';

    public const INACTIVE = 'inactive';

    public static function resolve(Tenant $tenant): string
    {
        if ($tenant->suspended_at !== null) {
            return self::SUSPENDED;
        }

        if (! $tenant->is_active) {
            return self::INACTIVE;
        }

        $now = now();

        if ($tenant->subscription_ends_at && $tenant->subscription_ends_at->isPast()) {
            return self::EXPIRED;
        }

        if ($tenant->trial_ends_at?->isPast() && ! $tenant->hasPaidSubscriptionBeyondTrial()) {
            return self::EXPIRED;
        }

        if (self::expiresWithinDays($tenant, 7)) {
            return self::EXPIRING;
        }

        if ($tenant->trial_ends_at && $tenant->trial_ends_at->isFuture()) {
            return self::TRIAL;
        }

        return self::RUNNING;
    }

    public static function expiresWithinDays(Tenant $tenant, int $days): bool
    {
        $threshold = now()->addDays($days);

        foreach ([$tenant->trial_ends_at, $tenant->subscription_ends_at] as $date) {
            if ($date instanceof Carbon && $date->isFuture() && $date->lte($threshold)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Tenant>  $query
     */
    public static function applyFilter($query, ?string $status): void
    {
        if ($status === null || $status === '' || $status === 'all') {
            return;
        }

        match ($status) {
            self::SUSPENDED => $query->whereNotNull('suspended_at'),
            self::INACTIVE => $query->where('is_active', false)->whereNull('suspended_at'),
            self::EXPIRED => $query->whereNull('suspended_at')
                ->where('is_active', true)
                ->whereNotNull('subscription_ends_at')
                ->where('subscription_ends_at', '<', now()),
            self::EXPIRING => $query->whereNull('suspended_at')
                ->where('is_active', true)
                ->where(function ($q) {
                    $threshold = now()->addDays(7);
                    $q->whereBetween('trial_ends_at', [now(), $threshold])
                        ->orWhereBetween('subscription_ends_at', [now(), $threshold]);
                }),
            self::TRIAL => $query->whereNull('suspended_at')
                ->where('is_active', true)
                ->whereNotNull('trial_ends_at')
                ->where('trial_ends_at', '>', now()),
            self::RUNNING => $query->whereNull('suspended_at')
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('subscription_ends_at')
                        ->orWhere('subscription_ends_at', '>=', now());
                })
                ->where(function ($q) {
                    $q->whereNull('trial_ends_at')
                        ->orWhere('trial_ends_at', '<=', now())
                        ->orWhere('trial_ends_at', '>', now()->addDays(7));
                }),
            default => null,
        };
    }
}
