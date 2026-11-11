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
use Spatie\Activitylog\Models\Activity;
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

        $hasPassword = filled($data['password'] ?? null);

        if (! $invite && ! $hasPassword) {
            throw ValidationException::withMessages([
                'owner_password' => [__('validation.required', ['attribute' => 'password'])],
            ]);
        }

        return DB::transaction(function () use ($tenant, $data, $causer, $invite, $hasPassword) {
            TenantRoleProvisioner::provision((int) $tenant->getKey());

            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $hasPassword
                    ? Hash::make($data['password'])
                    : Hash::make(Str::password(64)),
                'tenant_id' => $tenant->getKey(),
            ]);

            // Staff-set password → owner can log in immediately.
            if ($hasPassword) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            $registrar = app(PermissionRegistrar::class);
            $registrar->setPermissionsTeamId((int) $tenant->getKey());
            $user->assignRole('pharmacy owner');
            $registrar->setPermissionsTeamId(PlatformTeam::ID);

            activity()
                ->causedBy($causer)
                ->performedOn($tenant)
                ->tap(fn (Activity $activity) => $activity->tenant_id = $tenant->getKey())
                ->withProperties([
                    'owner_email' => $user->email,
                    'password_set' => $hasPassword,
                    'invited' => $invite,
                ])
                ->event($invite ? 'tenant.owner_invited' : 'tenant.owner_attached')
                ->log($invite
                    ? ($hasPassword
                        ? 'Pharmacy owner created with password and invitation sent'
                        : 'Pharmacy owner invitation sent')
                    : 'Pharmacy owner account created');

            if ($invite) {
                $this->sendInvitation->execute($user, $tenant);
            }

            return $user;
        });
    }

    public function updateCredentials(Tenant $tenant, array $data, User $causer, bool $invite = false): User
    {
        $owner = $this->findOwner($tenant);

        if ($owner === null) {
            throw ValidationException::withMessages([
                'tenant' => [__('platform.owner_invite_resend_missing')],
            ]);
        }

        $hasPassword = filled($data['password'] ?? null);

        if (! $invite && ! $hasPassword) {
            throw ValidationException::withMessages([
                'owner_password' => [__('validation.required', ['attribute' => 'password'])],
            ]);
        }

        return DB::transaction(function () use ($tenant, $owner, $data, $causer, $invite, $hasPassword) {
            if ($hasPassword) {
                $owner->forceFill([
                    'password' => Hash::make($data['password']),
                    'email_verified_at' => $owner->email_verified_at ?? now(),
                ])->save();
            }

            if ($invite) {
                $this->sendInvitation->execute($owner, $tenant);
            }

            activity()
                ->causedBy($causer)
                ->performedOn($tenant)
                ->tap(fn (Activity $activity) => $activity->tenant_id = $tenant->getKey())
                ->withProperties([
                    'owner_email' => $owner->email,
                    'password_set' => $hasPassword,
                    'invited' => $invite,
                ])
                ->event('tenant.owner_credentials_updated')
                ->log($invite && $hasPassword
                    ? 'Pharmacy owner password updated and invitation sent'
                    : ($hasPassword
                        ? 'Pharmacy owner password updated'
                        : 'Pharmacy owner invitation sent'));

            return $owner->fresh();
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
            ->tap(fn (Activity $activity) => $activity->tenant_id = $tenant->getKey())
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
