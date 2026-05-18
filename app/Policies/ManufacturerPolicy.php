<?php

namespace App\Policies;

use App\Domain\Catalog\Models\Manufacturer;
use App\Models\User;

class ManufacturerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manufacturers.view');
    }

    public function view(User $user, Manufacturer $manufacturer): bool
    {
        return $user->can('manufacturers.view') && (int) $user->tenant_id === (int) $manufacturer->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can('manufacturers.manage');
    }

    public function update(User $user, Manufacturer $manufacturer): bool
    {
        return $user->can('manufacturers.manage') && (int) $user->tenant_id === (int) $manufacturer->tenant_id;
    }

    public function delete(User $user, Manufacturer $manufacturer): bool
    {
        return $user->can('manufacturers.manage') && (int) $user->tenant_id === (int) $manufacturer->tenant_id;
    }
}
