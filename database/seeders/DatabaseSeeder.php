<?php

namespace Database\Seeders;

use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Billing\Models\TenantSubscription;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Manufacturer;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductBatch;
use App\Domain\Catalog\Models\StorageLocation;
use App\Support\Catalog\ProductUnitResolver;
use App\Support\Catalog\SeedDefaultProductTypes;
use App\Support\Tenant\BranchProvisioner;
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
                'features' => ['pos' => true, 'reports' => true, 'markup_pricing' => true, 'multi_branch' => false],
                'limits' => ['max_branches' => 1],
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
        $this->call(MasterCatalogSeeder::class);
        SeedDefaultProductTypes::forTenant((int) $tenant->getKey());
        $defaultBranch = BranchProvisioner::provisionForTenant((int) $tenant->getKey());

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
            ['1200', 'Inventory', 'asset'],
            ['2000', 'Accounts payable', 'liability'],
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

        $categories = collect([
            ['name' => 'General', 'slug' => 'general'],
            ['name' => 'Pain Relief', 'slug' => 'pain-relief'],
            ['name' => 'Antibiotic', 'slug' => 'antibiotic'],
            ['name' => 'Vitamins', 'slug' => 'vitamins'],
        ])->mapWithKeys(fn (array $row) => [
            $row['slug'] => Category::query()->withoutGlobalScopes()->create([
                'tenant_id' => $tenant->getKey(),
                'name' => $row['name'],
                'slug' => $row['slug'],
            ]),
        ]);

        $manufacturers = collect(['Demo Labs', 'Health Pharma', 'Care Remedies'])
            ->mapWithKeys(fn (string $name) => [
                $name => Manufacturer::query()->withoutGlobalScopes()->create([
                    'tenant_id' => $tenant->getKey(),
                    'name' => $name,
                ]),
            ]);

        $locations = collect([
            ['name' => 'Rack One', 'code' => 'R1', 'sort_order' => 10, 'notes' => 'Fast moving tablet strips.'],
            ['name' => 'Counter Shelf', 'code' => 'C1', 'sort_order' => 20, 'notes' => 'OTC and daily sale items.'],
            ['name' => 'Cold Shelf', 'code' => 'CS', 'sort_order' => 30, 'notes' => 'Temperature-sensitive products.'],
        ])->mapWithKeys(fn (array $row) => [
            $row['code'] => StorageLocation::query()->withoutGlobalScopes()->create([
                'tenant_id' => $tenant->getKey(),
                'branch_id' => $defaultBranch->getKey(),
                'name' => $row['name'],
                'code' => $row['code'],
                'sort_order' => $row['sort_order'],
                'is_active' => true,
                'notes' => $row['notes'],
            ]),
        ]);

        $paracetamol = Product::query()->withoutGlobalScopes()->create([
            'tenant_id' => $tenant->getKey(),
            'category_id' => $categories['pain-relief']->getKey(),
            'manufacturer_id' => $manufacturers['Demo Labs']->getKey(),
            'storage_location_id' => $locations['R1']->getKey(),
            'name' => 'Paracetamol 500mg',
            'generic_name' => 'Paracetamol',
            'strength' => '500 mg',
            'sku' => 'PAR-500',
            'barcode' => '8801234567890',
            'product_type' => 'tablet',
            'base_unit' => 'strip',
            'unit' => 'strip',
            'pieces_per_strip' => 10,
            'strips_per_box' => 12,
            'boxes_per_carton' => 24,
            'purchase_price' => 20,
            'sale_price' => 35,
            'default_markup_percent' => 75,
            'wholesale_price' => 32,
            'vat_percent' => 0,
            'short_description' => 'Common pain and fever relief tablet.',
            'min_stock' => 10,
            'is_active' => true,
        ]);

        ProductUnitResolver::syncProductUnits($paracetamol, [
            ['sell_unit' => 'strip', 'conversion_factor' => 1, 'purchase_price' => 20, 'sale_price' => 35, 'is_default' => true],
            ['sell_unit' => 'piece', 'conversion_factor' => 0.1, 'purchase_price' => 2, 'sale_price' => 3.5, 'is_default' => false],
            ['sell_unit' => 'box', 'conversion_factor' => 12, 'purchase_price' => 240, 'sale_price' => 420, 'is_default' => false],
            ['sell_unit' => 'carton', 'conversion_factor' => 288, 'purchase_price' => 5760, 'sale_price' => 10080, 'is_default' => false],
        ]);

        ProductBatch::query()->withoutGlobalScopes()->create([
            'tenant_id' => $tenant->getKey(),
            'branch_id' => $defaultBranch->getKey(),
            'product_id' => $paracetamol->getKey(),
            'storage_location_id' => $locations['R1']->getKey(),
            'batch_no' => 'B001',
            'expiry_date' => now()->addYear(),
            'manufactured_at' => now()->subMonths(2),
            'quantity_on_hand' => 240,
            'purchase_unit_cost' => 20,
            'sale_price' => 35,
            'markup_percent' => 75,
            'pack_sell_unit' => 'strip',
            'pack_conversion_factor' => 1,
        ]);

        ProductBatch::query()->withoutGlobalScopes()->create([
            'tenant_id' => $tenant->getKey(),
            'branch_id' => $defaultBranch->getKey(),
            'product_id' => $paracetamol->getKey(),
            'storage_location_id' => $locations['C1']->getKey(),
            'batch_no' => 'B002',
            'expiry_date' => now()->addMonths(8),
            'manufactured_at' => now()->subMonths(4),
            'quantity_on_hand' => 144,
            'purchase_unit_cost' => 240,
            'sale_price' => 408,
            'markup_percent' => 70,
            'pack_sell_unit' => 'box',
            'pack_conversion_factor' => 12,
        ]);

        $amoxicillin = Product::query()->withoutGlobalScopes()->create([
            'tenant_id' => $tenant->getKey(),
            'category_id' => $categories['antibiotic']->getKey(),
            'manufacturer_id' => $manufacturers['Health Pharma']->getKey(),
            'storage_location_id' => $locations['R1']->getKey(),
            'name' => 'Amoxicillin 250mg',
            'generic_name' => 'Amoxicillin',
            'strength' => '250 mg',
            'sku' => 'AMX-250',
            'barcode' => '8801234567891',
            'product_type' => 'capsule',
            'base_unit' => 'strip',
            'unit' => 'strip',
            'pieces_per_strip' => 10,
            'strips_per_box' => 10,
            'purchase_price' => 55,
            'sale_price' => 80,
            'default_markup_percent' => 45,
            'vat_percent' => 0,
            'short_description' => 'Demo antibiotic capsule with strip and box units.',
            'min_stock' => 15,
            'is_active' => true,
        ]);

        ProductUnitResolver::syncProductUnits($amoxicillin, [
            ['sell_unit' => 'strip', 'conversion_factor' => 1, 'purchase_price' => 55, 'sale_price' => 80, 'is_default' => true],
            ['sell_unit' => 'piece', 'conversion_factor' => 0.1, 'purchase_price' => 5.5, 'sale_price' => 8, 'is_default' => false],
            ['sell_unit' => 'box', 'conversion_factor' => 10, 'purchase_price' => 550, 'sale_price' => 800, 'is_default' => false],
        ]);

        ProductBatch::query()->withoutGlobalScopes()->create([
            'tenant_id' => $tenant->getKey(),
            'branch_id' => $defaultBranch->getKey(),
            'product_id' => $amoxicillin->getKey(),
            'storage_location_id' => $locations['R1']->getKey(),
            'batch_no' => 'AMX-LOT-01',
            'expiry_date' => now()->addMonths(10),
            'manufactured_at' => now()->subMonths(3),
            'quantity_on_hand' => 90,
            'purchase_unit_cost' => 55,
            'sale_price' => 80,
            'markup_percent' => 45.45,
            'pack_sell_unit' => 'strip',
            'pack_conversion_factor' => 1,
        ]);

        $coughSyrup = Product::query()->withoutGlobalScopes()->create([
            'tenant_id' => $tenant->getKey(),
            'category_id' => $categories['general']->getKey(),
            'manufacturer_id' => $manufacturers['Care Remedies']->getKey(),
            'storage_location_id' => $locations['C1']->getKey(),
            'name' => 'Cough Syrup 100ml',
            'generic_name' => 'Dextromethorphan',
            'strength' => '100 ml',
            'sku' => 'CS-100',
            'barcode' => '8801234567892',
            'product_type' => 'syrup',
            'base_unit' => 'piece',
            'unit' => 'piece',
            'purchase_price' => 75,
            'sale_price' => 110,
            'default_markup_percent' => 46.67,
            'vat_percent' => 5,
            'short_description' => 'Syrup demo product sold as one bottle/piece.',
            'min_stock' => 8,
            'is_active' => true,
        ]);

        ProductUnitResolver::syncProductUnits($coughSyrup, [
            ['sell_unit' => 'piece', 'conversion_factor' => 1, 'purchase_price' => 75, 'sale_price' => 110, 'is_default' => true],
        ]);

        ProductBatch::query()->withoutGlobalScopes()->create([
            'tenant_id' => $tenant->getKey(),
            'branch_id' => $defaultBranch->getKey(),
            'product_id' => $coughSyrup->getKey(),
            'storage_location_id' => $locations['C1']->getKey(),
            'batch_no' => 'CS-LOT-01',
            'expiry_date' => now()->addMonths(18),
            'manufactured_at' => now()->subMonth(),
            'quantity_on_hand' => 36,
            'purchase_unit_cost' => 75,
            'sale_price' => 110,
            'markup_percent' => 46.67,
            'pack_sell_unit' => 'piece',
            'pack_conversion_factor' => 1,
        ]);

        $vitamin = Product::query()->withoutGlobalScopes()->create([
            'tenant_id' => $tenant->getKey(),
            'category_id' => $categories['vitamins']->getKey(),
            'manufacturer_id' => $manufacturers['Health Pharma']->getKey(),
            'storage_location_id' => $locations['C1']->getKey(),
            'name' => 'Vitamin C 250mg',
            'generic_name' => 'Ascorbic Acid',
            'strength' => '250 mg',
            'sku' => 'VIT-C-250',
            'barcode' => '8801234567893',
            'product_type' => 'tablet',
            'base_unit' => 'strip',
            'unit' => 'strip',
            'pieces_per_strip' => 10,
            'strips_per_box' => 20,
            'purchase_price' => 30,
            'sale_price' => 45,
            'default_markup_percent' => 50,
            'wholesale_price' => 42,
            'vat_percent' => 0,
            'short_description' => 'Vitamin tablet for category and grid view demos.',
            'min_stock' => 20,
            'is_active' => true,
        ]);

        ProductUnitResolver::syncProductUnits($vitamin, [
            ['sell_unit' => 'strip', 'conversion_factor' => 1, 'purchase_price' => 30, 'sale_price' => 45, 'is_default' => true],
            ['sell_unit' => 'piece', 'conversion_factor' => 0.1, 'purchase_price' => 3, 'sale_price' => 4.5, 'is_default' => false],
            ['sell_unit' => 'box', 'conversion_factor' => 20, 'purchase_price' => 600, 'sale_price' => 900, 'is_default' => false],
        ]);

        ProductBatch::query()->withoutGlobalScopes()->create([
            'tenant_id' => $tenant->getKey(),
            'branch_id' => $defaultBranch->getKey(),
            'product_id' => $vitamin->getKey(),
            'storage_location_id' => $locations['C1']->getKey(),
            'batch_no' => 'VIT-01',
            'expiry_date' => now()->addMonths(14),
            'manufactured_at' => now()->subMonths(2),
            'quantity_on_hand' => 160,
            'purchase_unit_cost' => 30,
            'sale_price' => 45,
            'markup_percent' => 50,
            'pack_sell_unit' => 'strip',
            'pack_conversion_factor' => 1,
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
