<?php

namespace Database\Seeders;

use App\Domain\Tenant\Models\Tenant;
use App\Support\Permission\PlatformTeam;
use App\Support\Permission\TenantRoleProvisioner;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        $permissionNames = [
            'products.view', 'products.manage',
            'categories.view', 'categories.manage',
            'product_types.view', 'product_types.manage',
            'manufacturers.view', 'manufacturers.manage',
            'storage_locations.view', 'storage_locations.manage',
            'purchases.view', 'purchases.manage', 'purchases.view_all_branches',
            'sales.view',
            'returns.manage',
            'pos.access',
            'inventory.view', 'inventory.manage',
            'stock_transfers.view', 'stock_transfers.manage',
            'customers.view', 'customers.manage',
            'employees.view', 'employees.manage',
            'accounting.view', 'accounting.manage',
            'reports.view', 'reports.sales', 'reports.purchase', 'reports.inventory', 'reports.expiry',
            'reports.supplier', 'reports.customer', 'reports.finance', 'reports.branch', 'reports.activity',
            'reports.export', 'reports.print', 'reports.view_all_branches',
            'billing.manage',
            'suppliers.view', 'suppliers.manage',
            'team.users.view', 'team.users.manage',
            'settings.view', 'settings.manage',
            'branches.view', 'branches.manage',
            'sms.send',
            'promotions.view', 'promotions.manage',
            'notes.view', 'notes.manage',
            'platform.tenants', 'platform.subscriptions', 'platform.analytics',
        ];

        foreach ($permissionNames as $name) {
            Permission::query()->firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $registrar->setPermissionsTeamId(PlatformTeam::ID);
        $superAdmin = Role::query()->firstOrCreate(
            ['name' => 'super admin', 'guard_name' => 'web', 'tenant_id' => PlatformTeam::ID],
        );
        $superAdmin->syncPermissions(Permission::query()->get());

        foreach (Tenant::query()->cursor() as $tenant) {
            TenantRoleProvisioner::provision((int) $tenant->getKey());
        }
    }
}
