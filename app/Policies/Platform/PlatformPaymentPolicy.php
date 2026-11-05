<?php

namespace App\Policies\Platform;

use App\Domain\Billing\Models\PlatformPayment;
use App\Models\User;

final class PlatformPaymentPolicy extends PlatformPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isPlatformOperator($user) && (
            $this->isPlatformSuperAdmin($user) || $user->can('platform.payments.view')
        );
    }

    public function view(User $user, PlatformPayment $payment): bool
    {
        return $this->viewAny($user);
    }

    public function manage(User $user, ?PlatformPayment $payment = null): bool
    {
        return $this->isPlatformOperator($user) && (
            $this->isPlatformSuperAdmin($user) || $user->can('platform.payments.manage')
        );
    }
}
