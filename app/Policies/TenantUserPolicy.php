<?php

namespace App\Policies;

use App\Models\User;

class TenantUserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('team.users.view') && $user->tenant_id !== null;
    }

    public function create(User $user): bool
    {
        return $user->can('team.users.manage') && $user->tenant_id !== null;
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('team.users.manage')
            && $user->tenant_id !== null
            && (int) $user->tenant_id === (int) $model->tenant_id
            && ! $model->is_platform_super_admin;
    }

    public function delete(User $user, User $model): bool
    {
        return $user->can('team.users.manage')
            && $user->tenant_id !== null
            && (int) $user->tenant_id === (int) $model->tenant_id
            && $user->getKey() !== $model->getKey()
            && ! $model->is_platform_super_admin;
    }
}
