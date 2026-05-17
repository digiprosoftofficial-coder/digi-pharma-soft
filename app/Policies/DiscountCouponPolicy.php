<?php

namespace App\Policies;

use App\Domain\Sales\Models\DiscountCoupon;
use App\Models\User;

class DiscountCouponPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('promotions.view');
    }

    public function view(User $user, DiscountCoupon $discountCoupon): bool
    {
        return $user->can('promotions.view') && (int) $user->tenant_id === (int) $discountCoupon->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can('promotions.manage');
    }

    public function update(User $user, DiscountCoupon $discountCoupon): bool
    {
        return $user->can('promotions.manage') && (int) $user->tenant_id === (int) $discountCoupon->tenant_id;
    }

    public function delete(User $user, DiscountCoupon $discountCoupon): bool
    {
        return $user->can('promotions.manage') && (int) $user->tenant_id === (int) $discountCoupon->tenant_id;
    }
}
