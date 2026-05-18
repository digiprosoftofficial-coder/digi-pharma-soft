<?php

namespace App\Support\Permission;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class TenantRoleProvisioner
{
    public static function provision(int $tenantId): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($tenantId);

        $all = Permission::query()->whereNotIn('name', [
            'platform.tenants', 'platform.subscriptions', 'platform.analytics',
        ])->get();

        $cashier = Permission::query()->whereIn('name', [
            'pos.access', 'products.view', 'customers.view', 'sales.view',
        ])->get();

        $pharmacist = Permission::query()->whereIn('name', [
            'products.view', 'products.manage',
            'categories.view', 'categories.manage',
            'manufacturers.view', 'manufacturers.manage',
            'inventory.view', 'inventory.manage',
            'customers.view', 'reports.view', 'sales.view', 'returns.manage',
            'stock_transfers.view', 'suppliers.view', 'purchases.view',
        ])->get();

        $manager = Permission::query()->whereNotIn('name', [
            'platform.tenants', 'platform.subscriptions', 'platform.analytics', 'billing.manage',
        ])->get();

        $map = [
            'pharmacy owner' => $all,
            'manager' => $manager,
            'cashier' => $cashier,
            'pharmacist' => $pharmacist,
        ];

        foreach ($map as $roleName => $permissions) {
            $role = Role::query()->firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web', 'tenant_id' => $tenantId],
            );
            $role->syncPermissions($permissions);
        }
    }
}
