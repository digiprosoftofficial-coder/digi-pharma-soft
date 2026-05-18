<?php

namespace App\Policies\Platform;

use App\Domain\Billing\Models\PlatformInvoice;
use App\Models\User;

final class PlatformInvoicePolicy extends PlatformPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isPlatformSuperAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->isPlatformSuperAdmin($user);
    }

    public function view(User $user, PlatformInvoice $invoice): bool
    {
        return $this->isPlatformSuperAdmin($user);
    }

    public function update(User $user, PlatformInvoice $invoice): bool
    {
        return $this->isPlatformSuperAdmin($user)
            && $invoice->status === PlatformInvoice::STATUS_OPEN;
    }

    public function delete(User $user, PlatformInvoice $invoice): bool
    {
        return $this->update($user, $invoice);
    }
}
