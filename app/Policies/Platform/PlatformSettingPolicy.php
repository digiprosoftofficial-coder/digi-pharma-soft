<?php

namespace App\Policies\Platform;

use App\Models\User;

final class PlatformSettingPolicy extends PlatformPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isPlatformSuperAdmin($user);
    }

    public function update(User $user): bool
    {
        return $this->isPlatformSuperAdmin($user);
    }
}
