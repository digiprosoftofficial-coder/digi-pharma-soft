<?php

namespace App\Policies\Platform;

use App\Domain\Crm\Models\Lead;
use App\Models\User;

final class LeadPolicy extends PlatformPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canCrm($user);
    }

    public function view(User $user, Lead $lead): bool
    {
        return $this->canCrm($user);
    }

    public function create(User $user): bool
    {
        return $this->canCrm($user);
    }

    public function update(User $user, Lead $lead): bool
    {
        return $this->canCrm($user);
    }

    public function convert(User $user, Lead $lead): bool
    {
        // Only super admin provisions pharmacy / invite; sales tracks pipeline only.
        return $this->isPlatformSuperAdmin($user);
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $this->isPlatformSuperAdmin($user);
    }

    private function canCrm(User $user): bool
    {
        return $this->isPlatformOperator($user) && (
            $this->isPlatformSuperAdmin($user) || $user->can('platform.leads')
        );
    }
}
