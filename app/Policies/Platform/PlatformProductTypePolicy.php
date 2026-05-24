<?php

namespace App\Policies\Platform;

use App\Domain\Platform\Models\PlatformProductType;
use App\Models\User;

final class PlatformProductTypePolicy extends PlatformPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isPlatformSuperAdmin($user);
    }

    public function view(User $user, PlatformProductType $platformProductType): bool
    {
        return $this->isPlatformSuperAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->isPlatformSuperAdmin($user);
    }

    public function update(User $user, PlatformProductType $platformProductType): bool
    {
        return $this->isPlatformSuperAdmin($user);
    }

    public function delete(User $user, PlatformProductType $platformProductType): bool
    {
        return $this->isPlatformSuperAdmin($user);
    }
}
