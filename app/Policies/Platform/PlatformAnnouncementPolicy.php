<?php

namespace App\Policies\Platform;

use App\Domain\Platform\Models\PlatformAnnouncement;
use App\Models\User;

final class PlatformAnnouncementPolicy extends PlatformPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isPlatformSuperAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->isPlatformSuperAdmin($user);
    }

    public function update(User $user, PlatformAnnouncement $platformAnnouncement): bool
    {
        return $this->isPlatformSuperAdmin($user);
    }

    public function delete(User $user, PlatformAnnouncement $platformAnnouncement): bool
    {
        return $this->isPlatformSuperAdmin($user);
    }
}
