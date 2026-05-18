<?php

namespace App\Policies\Platform;

use App\Domain\Platform\Models\Reseller;
use App\Models\User;

final class ResellerPolicy extends PlatformPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isPlatformSuperAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->isPlatformSuperAdmin($user);
    }

    public function update(User $user, Reseller $reseller): bool
    {
        return $this->isPlatformSuperAdmin($user);
    }

    public function delete(User $user, Reseller $reseller): bool
    {
        return $this->isPlatformSuperAdmin($user);
    }
}
