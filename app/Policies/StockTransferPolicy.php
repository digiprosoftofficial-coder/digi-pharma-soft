<?php

namespace App\Policies;

use App\Domain\Inventory\Models\StockTransfer;
use App\Models\User;

class StockTransferPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('stock_transfers.view');
    }

    public function view(User $user, StockTransfer $stockTransfer): bool
    {
        return $user->can('stock_transfers.view') && (int) $user->tenant_id === (int) $stockTransfer->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can('stock_transfers.manage');
    }
}
