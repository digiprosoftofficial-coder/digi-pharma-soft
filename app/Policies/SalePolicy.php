<?php

namespace App\Policies;

use App\Domain\Sales\Models\Sale;
use App\Models\User;

class SalePolicy
{
    public function create(User $user): bool
    {
        return $user->can('pos.access');
    }

    public function view(User $user, Sale $sale): bool
    {
        return ($user->can('sales.view') || $user->can('pos.access'))
            && (int) $user->tenant_id === (int) $sale->tenant_id;
    }

    public function void(User $user, Sale $sale): bool
    {
        return $user->can('pos.access')
            && (int) $user->tenant_id === (int) $sale->tenant_id
            && $sale->status === 'posted';
    }
}
