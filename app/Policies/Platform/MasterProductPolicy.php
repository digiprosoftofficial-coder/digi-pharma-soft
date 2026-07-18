<?php

namespace App\Policies\Platform;

use App\Domain\Catalog\Models\MasterProduct;
use App\Models\User;

final class MasterProductPolicy extends PlatformPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isPlatformSuperAdmin($user);
    }

    public function view(User $user, MasterProduct $masterProduct): bool
    {
        return $this->isPlatformSuperAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->isPlatformSuperAdmin($user);
    }

    public function update(User $user, MasterProduct $masterProduct): bool
    {
        return $this->isPlatformSuperAdmin($user);
    }

    public function delete(User $user, MasterProduct $masterProduct): bool
    {
        return $this->isPlatformSuperAdmin($user);
    }
}
