<?php

namespace App\Policies;

use App\Domain\Sales\Models\SaleReturn;
use App\Models\User;

class SaleReturnPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('returns.manage');
    }

    public function view(User $user, SaleReturn $saleReturn): bool
    {
        return $user->can('returns.manage') && (int) $user->tenant_id === (int) $saleReturn->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can('returns.manage');
    }
}
