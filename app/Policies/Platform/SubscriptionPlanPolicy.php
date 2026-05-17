<?php

namespace App\Policies\Platform;

use App\Domain\Billing\Models\SubscriptionPlan;
use App\Models\User;

final class SubscriptionPlanPolicy extends PlatformPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isPlatformSuperAdmin($user);
    }

    public function view(User $user, SubscriptionPlan $subscriptionPlan): bool
    {
        return $this->isPlatformSuperAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->isPlatformSuperAdmin($user);
    }

    public function update(User $user, SubscriptionPlan $subscriptionPlan): bool
    {
        return $this->isPlatformSuperAdmin($user);
    }

    public function delete(User $user, SubscriptionPlan $subscriptionPlan): bool
    {
        return $this->isPlatformSuperAdmin($user);
    }
}
