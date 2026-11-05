<?php

namespace App\Policies\Platform;

use App\Models\User;

abstract class PlatformPolicy
{
    /**
     * Super Admin or Sales (platform console users).
     */
    protected function isPlatformOperator(User $user): bool
    {
        return $user->shouldUsePlatformDashboard();
    }

    /**
     * Super Admin only — full SaaS operations.
     */
    protected function isPlatformSuperAdmin(User $user): bool
    {
        return $user->isPlatformSuperAdminRole();
    }
}
