<?php

namespace App\Support\Tenant;

use App\Domain\Tenant\Models\Tenant;
use App\Models\User;

final class SupplierPaymentSettings
{
    public static function crossBranchEnabled(?Tenant $tenant): bool
    {
        if ($tenant === null) {
            return true;
        }

        $settings = $tenant->settings['supplier_payments'] ?? [];

        return ($settings['cross_branch'] ?? true) !== false;
    }

    public static function managersCanPay(?Tenant $tenant): bool
    {
        if ($tenant === null) {
            return false;
        }

        $settings = $tenant->settings['supplier_payments'] ?? [];

        return (bool) ($settings['managers_can_pay'] ?? false);
    }

    public static function userCanRecordPayment(?User $user): bool
    {
        if ($user === null || ! $user->can('purchases.manage')) {
            return false;
        }

        if ($user->hasRole('pharmacy owner')) {
            return true;
        }

        if ($user->hasRole('manager')) {
            return self::managersCanPay(tenant());
        }

        return false;
    }
}
