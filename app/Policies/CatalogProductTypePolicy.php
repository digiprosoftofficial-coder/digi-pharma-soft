<?php

namespace App\Policies;

use App\Domain\Catalog\Models\CatalogProductType;
use App\Models\User;

class CatalogProductTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('product_types.view');
    }

    public function view(User $user, CatalogProductType $catalogProductType): bool
    {
        return $user->can('product_types.view') && (int) $user->tenant_id === (int) $catalogProductType->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can('product_types.manage');
    }

    public function update(User $user, CatalogProductType $catalogProductType): bool
    {
        return $user->can('product_types.manage') && (int) $user->tenant_id === (int) $catalogProductType->tenant_id;
    }

    public function delete(User $user, CatalogProductType $catalogProductType): bool
    {
        return $user->can('product_types.manage') && (int) $user->tenant_id === (int) $catalogProductType->tenant_id;
    }
}
