<?php

namespace App\Domain\Tenant\Actions;

use App\Domain\Tenant\Models\Tenant;
use App\Models\User;

final class UnsuspendTenantAction
{
    public function execute(Tenant $tenant, User $causer): Tenant
    {
        $tenant->suspended_at = null;
        $tenant->save();

        activity()
            ->causedBy($causer)
            ->performedOn($tenant)
            ->tap(fn (\Spatie\Activitylog\Models\Activity $activity) => $activity->tenant_id = $tenant->getKey())
            ->event('tenant.unsuspended')
            ->log('Pharmacy unsuspended');

        return $tenant->fresh();
    }
}
