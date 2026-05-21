<?php

namespace App\Policies;

use App\Domain\Catalog\Models\StorageLocation;
use App\Models\User;

class StorageLocationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('storage_locations.view');
    }

    public function view(User $user, StorageLocation $storageLocation): bool
    {
        return $user->can('storage_locations.view')
            && (int) $user->tenant_id === (int) $storageLocation->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can('storage_locations.manage');
    }

    public function update(User $user, StorageLocation $storageLocation): bool
    {
        return $user->can('storage_locations.manage')
            && (int) $user->tenant_id === (int) $storageLocation->tenant_id;
    }

    public function delete(User $user, StorageLocation $storageLocation): bool
    {
        return $user->can('storage_locations.manage')
            && (int) $user->tenant_id === (int) $storageLocation->tenant_id;
    }
}
