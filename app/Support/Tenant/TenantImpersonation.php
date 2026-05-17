<?php

namespace App\Support\Tenant;

use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use Illuminate\Contracts\Session\Session;
use Spatie\Permission\PermissionRegistrar;

final class TenantImpersonation
{
    private const SESSION_TENANT = 'tenant_impersonation.tenant_id';

    private const SESSION_USER = 'tenant_impersonation.user_id';

    public function __construct(private readonly Session $session) {}

    public function isActive(): bool
    {
        return $this->session->has(self::SESSION_TENANT)
            && $this->session->has(self::SESSION_USER);
    }

    public function tenant(): ?Tenant
    {
        if (! $this->isActive()) {
            return null;
        }

        $tenantId = (int) $this->session->get(self::SESSION_TENANT);

        return Tenant::query()->whereKey($tenantId)->first();
    }

    public function actingUser(): ?User
    {
        if (! $this->isActive()) {
            return null;
        }

        $userId = (int) $this->session->get(self::SESSION_USER);
        $tenantId = (int) $this->session->get(self::SESSION_TENANT);

        $user = User::query()
            ->whereKey($userId)
            ->where('tenant_id', $tenantId)
            ->where('is_platform_super_admin', false)
            ->first();

        if ($user === null) {
            $this->clear();

            return null;
        }

        return $user;
    }

    public function start(Tenant $tenant, User $actingUser): void
    {
        $this->session->put(self::SESSION_TENANT, (int) $tenant->getKey());
        $this->session->put(self::SESSION_USER, (int) $actingUser->getKey());
    }

    public function clear(): void
    {
        $this->session->forget([self::SESSION_TENANT, self::SESSION_USER]);
    }

    public function resolveActingUserForTenant(Tenant $tenant): ?User
    {
        $tenantId = (int) $tenant->getKey();

        $candidates = User::query()
            ->where('tenant_id', $tenantId)
            ->where('is_platform_super_admin', false)
            ->orderBy('id')
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

        return $candidates->first();
    }
}
