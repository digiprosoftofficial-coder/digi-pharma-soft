<?php

namespace App\Policies;

use App\Domain\Tenant\Models\Branch;
use App\Models\User;
use App\Support\Tenant\TenantFeatures;

class BranchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('branches.view') && $this->featureEnabled();
    }

    public function view(User $user, Branch $branch): bool
    {
        return $user->can('branches.view')
            && (int) $user->tenant_id === (int) $branch->tenant_id
            && $this->featureEnabled();
    }

    public function create(User $user): bool
    {
        return $user->can('branches.manage') && $this->featureEnabled();
    }

    public function update(User $user, Branch $branch): bool
    {
        return $user->can('branches.manage')
            && (int) $user->tenant_id === (int) $branch->tenant_id
            && $this->featureEnabled();
    }

    public function delete(User $user, Branch $branch): bool
    {
        return $this->update($user, $branch);
    }

    private function featureEnabled(): bool
    {
        return TenantFeatures::multiBranchEnabled(tenant());
    }
}
