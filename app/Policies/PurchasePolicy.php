<?php

namespace App\Policies;

use App\Domain\Purchasing\Models\Purchase;
use App\Models\User;

class PurchasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('purchases.view');
    }

    public function view(User $user, Purchase $purchase): bool
    {
        return $user->can('purchases.view') && (int) $user->tenant_id === (int) $purchase->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can('purchases.manage');
    }
}
