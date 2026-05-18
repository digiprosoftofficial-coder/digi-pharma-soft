<?php

namespace App\Policies\Platform;

use App\Domain\Platform\Models\CatalogTemplate;
use App\Models\User;

final class CatalogTemplatePolicy extends PlatformPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isPlatformSuperAdmin($user);
    }

    public function view(User $user, CatalogTemplate $catalogTemplate): bool
    {
        return $this->isPlatformSuperAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->isPlatformSuperAdmin($user);
    }

    public function update(User $user, CatalogTemplate $catalogTemplate): bool
    {
        return $this->isPlatformSuperAdmin($user);
    }

    public function delete(User $user, CatalogTemplate $catalogTemplate): bool
    {
        return $this->isPlatformSuperAdmin($user);
    }

    public function apply(User $user, CatalogTemplate $catalogTemplate): bool
    {
        return $this->isPlatformSuperAdmin($user);
    }
}
