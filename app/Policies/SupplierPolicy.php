<?php

namespace App\Policies;

use App\Domain\Purchasing\Models\Supplier;
use App\Models\User;

class SupplierPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('suppliers.view');
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return $user->can('suppliers.view') && (int) $user->tenant_id === (int) $supplier->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can('suppliers.manage');
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $user->can('suppliers.manage') && (int) $user->tenant_id === (int) $supplier->tenant_id;
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->can('suppliers.manage') && (int) $user->tenant_id === (int) $supplier->tenant_id;
    }
}
