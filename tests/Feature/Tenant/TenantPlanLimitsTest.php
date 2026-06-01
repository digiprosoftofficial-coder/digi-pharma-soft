<?php

namespace Tests\Feature\Tenant;

use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Catalog\Models\Product;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use App\Support\Catalog\ProductImportCsv;
use App\Support\Tenant\TenantLimits;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class TenantPlanLimitsTest extends TestCase
{
    use RefreshDatabase;

    public function test_limits_default_to_unlimited_when_plan_omits_them(): void
    {
        $this->seed(DatabaseSeeder::class);
        $tenant = Tenant::query()->firstOrFail();

        $this->assertNull(TenantLimits::maxProducts($tenant));
        $this->assertNull(TenantLimits::maxImportRows($tenant));
        $this->assertFalse(TenantLimits::productLimitReached($tenant));
    }

    public function test_manual_create_blocked_when_product_limit_reached(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $tenant = Tenant::query()->firstOrFail();

        $existing = Product::query()->count();
        $this->setPlanLimits($tenant, ['max_products' => $existing + 1]);

        $this->actingAs($user)->post('/products', $this->productPayload('First Product'))
            ->assertRedirect(route('tenant.products.index'));

        $this->actingAs($user)
            ->from(route('tenant.products.create'))
            ->post('/products', $this->productPayload('Second Product'))
            ->assertRedirect(route('tenant.products.create'))
            ->assertSessionHasErrors('name');

        $this->assertSame($existing + 1, Product::query()->count());
    }

    public function test_import_rejected_when_rows_exceed_plan_limit(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $tenant = Tenant::query()->firstOrFail();

        $this->setPlanLimits($tenant, ['max_import_rows' => 1]);

        $csv = implode(',', ProductImportCsv::HEADERS)."\n";
        $csv .= 'Row One,,,R1,,tablet,strip,10,,,general,Demo Labs,,10,18,,,,,,,,1'."\n";
        $csv .= 'Row Two,,,R2,,tablet,strip,10,,,general,Demo Labs,,10,18,,,,,,,,1'."\n";

        $file = UploadedFile::fake()->createWithContent('products.csv', $csv);

        $this->actingAs($user)
            ->from(route('tenant.catalog.import.index'))
            ->post('/catalog/import', ['file' => $file, 'skip_duplicates' => true])
            ->assertRedirect(route('tenant.catalog.import.index'))
            ->assertSessionHasErrors('file');

        $this->assertSame(0, Product::query()->whereIn('sku', ['R1', 'R2'])->count());
    }

    public function test_import_stops_creating_at_product_limit(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $tenant = Tenant::query()->firstOrFail();

        $existing = Product::query()->count();
        $this->setPlanLimits($tenant, ['max_products' => $existing + 1]);

        $csv = implode(',', ProductImportCsv::HEADERS)."\n";
        $csv .= 'Cap One,,,CAP1,,tablet,strip,10,,,general,Demo Labs,,10,18,,,,,,,,1'."\n";
        $csv .= 'Cap Two,,,CAP2,,tablet,strip,10,,,general,Demo Labs,,10,18,,,,,,,,1'."\n";

        $file = UploadedFile::fake()->createWithContent('products.csv', $csv);

        $this->actingAs($user)
            ->post('/catalog/import', ['file' => $file, 'skip_duplicates' => true])
            ->assertRedirect(route('tenant.catalog.import.index'));

        $this->assertSame(1, Product::query()->whereIn('sku', ['CAP1', 'CAP2'])->count());
    }

    public function test_super_admin_can_save_plan_limits(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->post('/platform/plans', [
                'name' => 'Capped',
                'slug' => 'capped',
                'price_cents' => 1900,
                'trial_days' => 7,
                'features' => ['pos' => true, 'reports' => true],
                'limits' => ['max_products' => 500, 'max_import_rows' => 100],
            ])
            ->assertRedirect(route('platform.plans.index'));

        $plan = SubscriptionPlan::query()->where('slug', 'capped')->firstOrFail();
        $this->assertSame(500, $plan->limits['max_products']);
        $this->assertSame(100, $plan->limits['max_import_rows']);
    }

    public function test_super_admin_can_override_tenant_limits(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $tenant = Tenant::query()->firstOrFail();

        $this->setPlanLimits($tenant, ['max_products' => 100]);
        $this->assertSame(100, TenantLimits::maxProducts($tenant));

        $this->actingAs($admin)
            ->put("/platform/tenants/{$tenant->id}", [
                'name' => $tenant->name,
                'is_active' => true,
                'max_products_override' => 500,
                'max_import_rows_override' => 50,
            ])
            ->assertRedirect();

        $tenant->refresh();
        $this->assertSame(500, TenantLimits::maxProducts($tenant));
        $this->assertSame(50, TenantLimits::maxImportRows($tenant));
    }

    public function test_tenant_limit_override_can_be_cleared_to_inherit(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $tenant = Tenant::query()->firstOrFail();

        $this->setPlanLimits($tenant, ['max_products' => 100]);

        $settings = $tenant->settings ?? [];
        $settings['limits'] = ['max_products' => 999];
        $tenant->settings = $settings;
        $tenant->save();
        $tenant->refresh();

        $this->assertSame(999, TenantLimits::maxProducts($tenant));

        $this->actingAs($admin)
            ->put("/platform/tenants/{$tenant->id}", [
                'name' => $tenant->name,
                'is_active' => true,
                'max_products_override' => '',
            ])
            ->assertRedirect();

        $tenant->refresh();
        $this->assertSame(100, TenantLimits::maxProducts($tenant));
    }

    private function setPlanLimits(Tenant $tenant, array $limits): void
    {
        $plan = $tenant->activeSubscription?->plan;
        $plan->limits = $limits;
        $plan->save();
        $tenant->refresh();
    }

    private function productPayload(string $name): array
    {
        return [
            'name' => $name,
            'product_type' => 'tablet',
            'base_unit' => 'strip',
            'units' => [
                ['sell_unit' => 'strip', 'conversion_factor' => 1, 'purchase_price' => 20, 'sale_price' => 35, 'is_default' => true],
            ],
            'min_stock' => 0,
            'is_active' => true,
        ];
    }
}
