<?php

namespace App\Domain\Billing\Services;

use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Billing\Models\TenantSubscription;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use App\Support\Platform\PlatformSettings;
use Illuminate\Support\Facades\DB;

final class TrialManagementService
{
    public function startTrial(Tenant $tenant, ?SubscriptionPlan $plan = null, ?int $days = null): Tenant
    {
        $plan ??= $tenant->activeSubscription?->plan;
        $days ??= (int) ($plan?->trial_days ?: PlatformSettings::defaultTrialDays());

        $tenant->forceFill([
            'billing_status' => 'trialing',
            'trial_ends_at' => now()->addDays($days),
            'subscription_ends_at' => now()->addDays($days),
            'payment_failed_at' => null,
            'grace_period_ends_at' => null,
        ])->save();

        return $tenant->fresh();
    }

    /**
     * Convert trial to paid period without payment (manual Super Admin extension).
     */
    public function convertToPaid(
        Tenant $tenant,
        string $interval = 'monthly',
        ?SubscriptionPlan $plan = null,
        ?User $actor = null,
    ): Tenant {
        return DB::transaction(function () use ($tenant, $interval, $plan, $actor) {
            $plan ??= $tenant->activeSubscription?->plan;
            $ends = $interval === 'yearly' ? now()->addYear() : now()->addMonth();

            $tenant->forceFill([
                'billing_status' => 'active',
                'subscription_ends_at' => $ends,
                'payment_failed_at' => null,
                'grace_period_ends_at' => null,
            ])->save();

            if ($plan) {
                $existing = $tenant->activeSubscription;
                if ($existing) {
                    $existing->forceFill([
                        'status' => 'replaced',
                        'cancelled_at' => now(),
                        'ends_at' => now(),
                    ])->save();
                }

                TenantSubscription::query()->create([
                    'tenant_id' => $tenant->getKey(),
                    'subscription_plan_id' => $plan->getKey(),
                    'starts_at' => now(),
                    'ends_at' => $ends,
                    'status' => 'active',
                    'billing_interval' => $interval,
                ]);
            } elseif ($tenant->activeSubscription) {
                $tenant->activeSubscription->forceFill([
                    'billing_interval' => $interval,
                    'ends_at' => $ends,
                    'status' => 'active',
                ])->save();
            }

            if ($actor) {
                activity()
                    ->causedBy($actor)
                    ->performedOn($tenant)
                    ->tap(fn ($a) => $a->tenant_id = $tenant->getKey())
                    ->event('billing.trial_converted')
                    ->log('Trial converted to paid subscription');
            }

            return $tenant->fresh(['activeSubscription.plan']);
        });
    }

    public function extend(Tenant $tenant, int $days, ?User $actor = null): Tenant
    {
        $base = $tenant->subscription_ends_at && $tenant->subscription_ends_at->isFuture()
            ? $tenant->subscription_ends_at->copy()
            : now();

        $tenant->forceFill([
            'subscription_ends_at' => $base->addDays($days),
            'billing_status' => 'active',
            'is_active' => true,
            'suspended_at' => null,
        ])->save();

        if ($actor) {
            activity()
                ->causedBy($actor)
                ->performedOn($tenant)
                ->tap(fn ($a) => $a->tenant_id = $tenant->getKey())
                ->withProperties(['days' => $days])
                ->event('billing.subscription_extended')
                ->log('Subscription extended manually');
        }

        return $tenant->fresh();
    }
}
