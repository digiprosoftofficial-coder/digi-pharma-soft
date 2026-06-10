<?php

namespace App\Policies;

use App\Domain\Purchasing\Models\PurchaseReturn;
use App\Models\User;

class PurchaseReturnPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('purchases.view');
    }

    public function view(User $user, PurchaseReturn $purchaseReturn): bool
    {
        return $user->can('purchases.view')
            && (int) $user->tenant_id === (int) $purchaseReturn->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can('purchases.manage');
    }
}
