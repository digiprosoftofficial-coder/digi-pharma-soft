<?php

namespace App\Policies;

use App\Domain\Accounting\Models\LedgerAccount;
use App\Models\User;

class LedgerAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('accounting.view');
    }

    public function view(User $user, LedgerAccount $ledgerAccount): bool
    {
        return $user->can('accounting.view') && (int) $user->tenant_id === (int) $ledgerAccount->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can('accounting.manage');
    }

    public function update(User $user, LedgerAccount $ledgerAccount): bool
    {
        return $user->can('accounting.manage') && (int) $user->tenant_id === (int) $ledgerAccount->tenant_id;
    }
}
