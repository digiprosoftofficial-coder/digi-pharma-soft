<?php

namespace App\Policies\Platform;

use App\Models\User;

abstract class PlatformPolicy
{
    protected function isPlatformSuperAdmin(User $user): bool
    {
        return $user->shouldUsePlatformDashboard();
    }
}
