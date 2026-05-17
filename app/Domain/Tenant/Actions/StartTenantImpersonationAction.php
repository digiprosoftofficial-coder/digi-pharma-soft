<?php

namespace App\Domain\Tenant\Actions;

use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use App\Support\Tenant\TenantImpersonation;
use Illuminate\Validation\ValidationException;

final class StartTenantImpersonationAction
{
    public function __construct(private readonly TenantImpersonation $impersonation) {}

    public function execute(Tenant $tenant, User $platformAdmin): User
    {
        $actingUser = $this->impersonation->resolveActingUserForTenant($tenant);

        if ($actingUser === null) {
            throw ValidationException::withMessages([
                'tenant' => [__('platform.impersonate_no_users')],
            ]);
        }

        $this->impersonation->start($tenant, $actingUser);

        activity()
            ->causedBy($platformAdmin)
            ->performedOn($tenant)
            ->tap(fn (\Spatie\Activitylog\Models\Activity $activity) => $activity->tenant_id = $tenant->getKey())
            ->event('tenant.impersonation_started')
            ->withProperties([
                'acting_user_id' => $actingUser->getKey(),
                'acting_user_email' => $actingUser->email,
            ])
            ->log('Platform admin started tenant impersonation');

        return $actingUser;
    }
}
