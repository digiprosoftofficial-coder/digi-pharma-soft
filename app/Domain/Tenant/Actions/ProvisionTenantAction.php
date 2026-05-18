<?php

namespace App\Domain\Tenant\Actions;

use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Billing\Models\TenantSubscription;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use App\Support\Permission\TenantRoleProvisioner;
use App\Support\Platform\PlatformSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ProvisionTenantAction
{
    public function __construct(private readonly AttachTenantOwnerAction $attachOwner) {}
    /**
     * @param  array{
     *   name: string,
     *   slug: string,
     *   subscription_plan_id: int,
     *   trial_ends_at?: string|null,
     *   subscription_ends_at?: string|null,
     *   add_owner_later?: bool,
     *   owner_name?: string|null,
     *   owner_email?: string|null,
     *   owner_password?: string|null,
     *   owner_invite?: bool,
     * }  $data
     */
    public function execute(array $data, User $causer): Tenant
    {
        return DB::transaction(function () use ($data, $causer) {
            $plan = SubscriptionPlan::query()->findOrFail($data['subscription_plan_id']);

            $trialDays = (int) ($plan->trial_days ?: PlatformSettings::defaultTrialDays());
            $trialEnds = isset($data['trial_ends_at'])
                ? \Illuminate\Support\Carbon::parse($data['trial_ends_at'])
                : now()->addDays($trialDays);

            $subscriptionEnds = isset($data['subscription_ends_at']) && $data['subscription_ends_at'] !== ''
                ? \Illuminate\Support\Carbon::parse($data['subscription_ends_at'])
                : now()->addYear();

            $tenant = Tenant::query()->create([
                'name' => $data['name'],
                'slug' => Str::lower($data['slug']),
                'reseller_id' => $data['reseller_id'] ?? null,
                'is_active' => true,
                'billing_status' => 'trialing',
                'trial_ends_at' => $trialEnds,
                'subscription_ends_at' => $subscriptionEnds,
                'settings' => PlatformSettings::defaultTenantSettings(),
            ]);

            TenantSubscription::query()->create([
                'tenant_id' => $tenant->getKey(),
                'subscription_plan_id' => $plan->getKey(),
                'starts_at' => now(),
                'ends_at' => $subscriptionEnds,
                'status' => 'active',
            ]);

            TenantRoleProvisioner::provision((int) $tenant->getKey());

            if (empty($data['add_owner_later'])) {
                $this->attachOwner->execute($tenant, [
                    'name' => $data['owner_name'],
                    'email' => $data['owner_email'],
                    'password' => $data['owner_password'] ?? null,
                ], $causer, ! empty($data['owner_invite']));
            }

            activity()
                ->causedBy($causer)
                ->performedOn($tenant)
                ->tap(fn (\Spatie\Activitylog\Models\Activity $activity) => $activity->tenant_id = $tenant->getKey())
                ->withProperties([
                    'plan_id' => $plan->getKey(),
                    'slug' => $tenant->slug,
                    'owner_skipped' => ! empty($data['add_owner_later']),
                ])
                ->event('tenant.provisioned')
                ->log('Pharmacy provisioned');

            return $tenant->load(['activeSubscription.plan', 'users']);
        });
    }
}
