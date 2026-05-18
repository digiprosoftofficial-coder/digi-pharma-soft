<?php

namespace Database\Seeders;

use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Billing\Models\TenantSubscription;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Manufacturer;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductBatch;
use App\Support\Catalog\ProductUnitResolver;
use App\Domain\Accounting\Models\LedgerAccount;
use App\Domain\Purchasing\Models\Supplier;
use App\Domain\Sales\Models\DiscountCoupon;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use App\Support\Permission\PlatformTeam;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $plan = SubscriptionPlan::query()->firstOrCreate(
            ['slug' => 'professional'],
            [
                'name' => 'Professional',
                'price_cents' => 4900,
                'trial_days' => 14,
                'features' => ['pos' => true, 'reports' => true],
            ],
        );

        $tenant = Tenant::query()->create([
            'name' => 'Demo Pharmacy',
            'slug' => 'demo-pharmacy',
            'is_active' => true,
            'trial_ends_at' => now()->addDays(30),
            'subscription_ends_at' => now()->addYear(),
        ]);

        TenantSubscription::query()->create([
            'tenant_id' => $tenant->getKey(),
            'subscription_plan_id' => $plan->getKey(),
            'starts_at' => now(),
            'ends_at' => now()->addYear(),
            'status' => 'active',
        ]);

        $this->call(RolePermissionSeeder::class);

        $registrar = app(PermissionRegistrar::class);

        $registrar->setPermissionsTeamId(PlatformTeam::ID);
        $platform = User::query()->create([
            'name' => 'Platform Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'tenant_id' => null,
            'is_platform_super_admin' => true,
            'email_verified_at' => now(),
        ]);
        $platform->assignRole('super admin');

        $registrar->setPermissionsTeamId($tenant->getKey());

        foreach ([
            ['1000', 'Cash on hand', 'asset'],
            ['4000', 'Sales revenue', 'income'],
            ['5000', 'Cost of goods sold', 'expense'],
        ] as [$code, $name, $type]) {
            LedgerAccount::query()->withoutGlobalScopes()->firstOrCreate(
                ['tenant_id' => $tenant->getKey(), 'code' => $code],
                ['name' => $name, 'type' => $type],
            );
        }

        DiscountCoupon::query()->withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenant->getKey(), 'code' => 'SAVE10'],
            ['percent_off' => 10, 'expires_at' => now()->addMonths(6), 'is_active' => true],
        );

        $category = Category::query()->withoutGlobalScopes()->create([
            'tenant_id' => $tenant->getKey(),
            'name' => 'General',
            'slug' => 'general',
        ]);
        $manufacturer = Manufacturer::query()->withoutGlobalScopes()->create([
            'tenant_id' => $tenant->getKey(),
            'name' => 'Demo Labs',
        ]);

        $product = Product::query()->withoutGlobalScopes()->create([
            'tenant_id' => $tenant->getKey(),
            'category_id' => $category->getKey(),
            'manufacturer_id' => $manufacturer->getKey(),
            'name' => 'Paracetamol 500mg',
            'sku' => 'PAR-500',
            'barcode' => '8801234567890',
            'product_type' => 'tablet',
            'base_unit' => 'strip',
            'unit' => 'strip',
            'purchase_price' => 20,
            'sale_price' => 35,
            'min_stock' => 10,
            'is_active' => true,
        ]);

        ProductUnitResolver::syncProductUnits($product, [
            ['sell_unit' => 'strip', 'conversion_factor' => 1, 'purchase_price' => 20, 'sale_price' => 35, 'is_default' => true],
            ['sell_unit' => 'box', 'conversion_factor' => 10, 'purchase_price' => 180, 'sale_price' => 320, 'is_default' => false],
        ]);

        ProductBatch::query()->withoutGlobalScopes()->create([
            'tenant_id' => $tenant->getKey(),
            'product_id' => $product->getKey(),
            'batch_no' => 'B001',
            'expiry_date' => now()->addYear(),
            'quantity_on_hand' => 500,
            'purchase_unit_cost' => 20,
        ]);

        Supplier::query()->withoutGlobalScopes()->create([
            'tenant_id' => $tenant->getKey(),
            'name' => 'Main Supplier',
            'phone' => '+8801700000000',
        ]);

        $owner = User::query()->create([
            'name' => 'Pharmacy Owner',
            'email' => 'owner@example.com',
            'password' => Hash::make('password'),
            'tenant_id' => $tenant->getKey(),
            'is_platform_super_admin' => false,
            'email_verified_at' => now(),
        ]);
        $registrar->setPermissionsTeamId($tenant->getKey());
        $owner->assignRole('pharmacy owner');
    }
}
