<?php

namespace App\Policies\Platform;

use App\Domain\Catalog\Models\MasterProductSuggestion;
use App\Models\User;

final class MasterProductSuggestionPolicy extends PlatformPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isPlatformSuperAdmin($user);
    }

    public function view(User $user, MasterProductSuggestion $suggestion): bool
    {
        return $this->isPlatformSuperAdmin($user);
    }

    public function update(User $user, MasterProductSuggestion $suggestion): bool
    {
        return $this->isPlatformSuperAdmin($user);
    }
}
