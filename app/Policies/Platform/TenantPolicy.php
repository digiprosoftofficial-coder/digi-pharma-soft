<?php

namespace App\Policies\Platform;

use App\Domain\Tenant\Models\Tenant;
use App\Models\User;

final class TenantPolicy extends PlatformPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isPlatformSuperAdmin($user);
    }

    public function view(User $user, Tenant $tenant): bool
    {
        return $this->isPlatformSuperAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->isPlatformSuperAdmin($user);
    }

    public function update(User $user, Tenant $tenant): bool
    {
        return $this->isPlatformSuperAdmin($user);
    }

    public function suspend(User $user, Tenant $tenant): bool
    {
        return $this->isPlatformSuperAdmin($user);
    }

    public function attachOwner(User $user, Tenant $tenant): bool
    {
        return $this->isPlatformSuperAdmin($user);
    }

    public function impersonate(User $user, Tenant $tenant): bool
    {
        return $this->isPlatformSuperAdmin($user);
    }
}
