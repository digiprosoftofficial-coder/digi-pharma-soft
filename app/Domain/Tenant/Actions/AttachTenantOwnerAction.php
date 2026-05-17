<?php

namespace App\Domain\Tenant\Actions;

use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use App\Support\Permission\PlatformTeam;
use App\Support\Permission\TenantRoleProvisioner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

final class AttachTenantOwnerAction
{
    public function __construct(private readonly SendTenantOwnerInvitationAction $sendInvitation) {}

    /**
     * @param  array{name: string, email: string, password?: string}  $data
     */
    public function execute(Tenant $tenant, array $data, User $causer, bool $invite = false): User
    {
        if ($this->tenantHasOwner($tenant)) {
            throw ValidationException::withMessages([
                'owner_email' => [__('platform.owner_already_exists')],
            ]);
        }

        if (! $invite && empty($data['password'])) {
            throw ValidationException::withMessages([
                'owner_password' => [__('validation.required', ['attribute' => 'password'])],
            ]);
        }

        return DB::transaction(function () use ($tenant, $data, $causer, $invite) {
            TenantRoleProvisioner::provision((int) $tenant->getKey());

            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $invite ? Hash::make(Str::password(64)) : Hash::make($data['password']),
                'tenant_id' => $tenant->getKey(),
                'is_platform_super_admin' => false,
                'email_verified_at' => $invite ? null : now(),
            ]);

            $registrar = app(PermissionRegistrar::class);
            $registrar->setPermissionsTeamId((int) $tenant->getKey());
            $user->assignRole('pharmacy owner');
            $registrar->setPermissionsTeamId(PlatformTeam::ID);

            if ($invite) {
                $this->sendInvitation->execute($user, $tenant);

                activity()
                    ->causedBy($causer)
                    ->performedOn($tenant)
                    ->tap(fn (\Spatie\Activitylog\Models\Activity $activity) => $activity->tenant_id = $tenant->getKey())
                    ->withProperties(['owner_email' => $user->email])
                    ->event('tenant.owner_invited')
                    ->log('Pharmacy owner invitation sent');
            } else {
                activity()
                    ->causedBy($causer)
                    ->performedOn($tenant)
                    ->tap(fn (\Spatie\Activitylog\Models\Activity $activity) => $activity->tenant_id = $tenant->getKey())
                    ->withProperties(['owner_email' => $user->email])
                    ->event('tenant.owner_attached')
                    ->log('Pharmacy owner account created');
            }

            return $user;
        });
    }

    public function resendInvitation(Tenant $tenant, User $causer): User
    {
        $owner = $this->findOwner($tenant);

        if ($owner === null) {
            throw ValidationException::withMessages([
                'tenant' => [__('platform.owner_invite_resend_missing')],
            ]);
        }

        if ($owner->email_verified_at !== null) {
            throw ValidationException::withMessages([
                'tenant' => [__('platform.owner_invite_resend_already_active')],
            ]);
        }

        $this->sendInvitation->execute($owner, $tenant);

        activity()
            ->causedBy($causer)
            ->performedOn($tenant)
            ->tap(fn (\Spatie\Activitylog\Models\Activity $activity) => $activity->tenant_id = $tenant->getKey())
            ->withProperties(['owner_email' => $owner->email])
            ->event('tenant.owner_invite_resent')
            ->log('Pharmacy owner invitation resent');

        return $owner;
    }

    public function findOwner(Tenant $tenant): ?User
    {
        $tenantId = (int) $tenant->getKey();

        $candidates = User::query()
            ->where('tenant_id', $tenantId)
            ->where('is_platform_super_admin', false)
            ->get();

        if ($candidates->isEmpty()) {
            return null;
        }

        $registrar = app(PermissionRegistrar::class);
        $previousTeam = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId($tenantId);

        try {
            foreach ($candidates as $user) {
                if ($user->hasRole('pharmacy owner')) {
                    return $user;
                }
            }
        } finally {
            $registrar->setPermissionsTeamId($previousTeam);
        }

        return null;
    }

    public function tenantHasOwner(Tenant $tenant): bool
    {
        return $this->findOwner($tenant) !== null;
    }
}
