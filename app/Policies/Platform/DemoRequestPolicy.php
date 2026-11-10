<?php

namespace App\Policies\Platform;

use App\Domain\Crm\Models\DemoRequest;
use App\Models\User;

final class DemoRequestPolicy extends PlatformPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canDemos($user);
    }

    public function view(User $user, DemoRequest $demoRequest): bool
    {
        return $this->canDemos($user);
    }

    public function update(User $user, DemoRequest $demoRequest): bool
    {
        return $this->canDemos($user);
    }

    public function delete(User $user, DemoRequest $demoRequest): bool
    {
        return $this->isPlatformSuperAdmin($user);
    }

    private function canDemos(User $user): bool
    {
        return $this->isPlatformOperator($user) && (
            $this->isPlatformSuperAdmin($user) || $user->can('platform.demos')
        );
    }
}
