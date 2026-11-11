<?php

namespace App\Policies\Platform;

use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

final class TenantPolicy extends PlatformPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isPlatformSuperAdmin($user);
    }

    public function view(User $user, Tenant $tenant): bool
    {
        if ($this->isPlatformSuperAdmin($user)) {
            return true;
        }

        return $this->canCreatePharmacy($user) && $this->provisionedBy($user, $tenant);
    }

    public function create(User $user): bool
    {
        return $this->isPlatformSuperAdmin($user) || $this->canCreatePharmacy($user);
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
        if ($this->isPlatformSuperAdmin($user)) {
            return true;
        }

        return $this->canCreatePharmacy($user) && $this->provisionedBy($user, $tenant);
    }

    public function impersonate(User $user, Tenant $tenant): bool
    {
        return $this->isPlatformSuperAdmin($user);
    }

    public function exportData(User $user, Tenant $tenant): bool
    {
        return $this->isPlatformSuperAdmin($user);
    }

    public function purgeData(User $user, Tenant $tenant): bool
    {
        return $this->isPlatformSuperAdmin($user);
    }

    private function canCreatePharmacy(User $user): bool
    {
        return $this->isPlatformOperator($user) && $user->can('platform.tenants.create');
    }

    private function provisionedBy(User $user, Tenant $tenant): bool
    {
        return Activity::query()
            ->where('causer_id', $user->getKey())
            ->where('causer_type', $user->getMorphClass())
            ->where('subject_type', $tenant->getMorphClass())
            ->where('subject_id', $tenant->getKey())
            ->where('event', 'tenant.provisioned')
            ->exists();
    }
}
