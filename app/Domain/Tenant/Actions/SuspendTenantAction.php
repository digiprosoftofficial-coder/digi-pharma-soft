<?php

namespace App\Domain\Tenant\Actions;

use App\Domain\Tenant\Models\Tenant;
use App\Models\User;

final class SuspendTenantAction
{
    public function execute(Tenant $tenant, User $causer, ?string $reason = null): Tenant
    {
        $tenant->suspended_at = now();
        $tenant->save();

        activity()
            ->causedBy($causer)
            ->performedOn($tenant)
            ->tap(fn (\Spatie\Activitylog\Models\Activity $activity) => $activity->tenant_id = $tenant->getKey())
            ->withProperties(['reason' => $reason])
            ->event('tenant.suspended')
            ->log('Pharmacy suspended');

        return $tenant->fresh();
    }
}
