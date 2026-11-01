<?php

namespace Tests\Feature\Platform;

use App\Domain\Catalog\Models\MasterProduct;
use App\Domain\Catalog\Models\Product;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use App\Support\Catalog\MasterCatalogSyncStatus;
use App\Support\Tenant\TenantLimits;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformMasterCatalogSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_add_all_master_medicines_to_pharmacy(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $tenant = Tenant::query()->firstOrFail();

        $master = MasterProduct::query()->create([
            'name' => 'Bulk Sync Med',
            'generic_name' => 'Test Generic',
            'sku' => 'MSTR-BULK-SYNC-1',
            'product_type' => 'tablet',
            'base_unit' => 'strip',
            'mrp' => 10,
            'default_purchase_price' => 8,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('platform.tenants.show', $tenant))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Platform/Tenants/Show')
                ->where('masterCatalogSync.can_add', true)
                ->where('masterCatalogSync.pending', fn ($pending) => $pending >= 1));

        $before = $this->tenantProductCount($tenant);

        $this->actingAs($admin)
            ->from(route('platform.tenants.show', $tenant))
            ->post(route('platform.tenants.master-catalog', $tenant))
            ->assertRedirect(route('platform.tenants.show', $tenant));

        $this->assertTrue(
            Product::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenant->getKey())
                ->where('master_product_id', $master->getKey())
                ->exists()
        );
        $this->assertGreaterThan($before, $this->tenantProductCount($tenant));
        $this->assertSame(
            MasterCatalogSyncStatus::STATUS_COMPLETED,
            MasterCatalogSyncStatus::raw($tenant->fresh())['status'] ?? null
        );

        $afterFirst = $this->tenantProductCount($tenant);

        $this->actingAs($admin)
            ->post(route('platform.tenants.master-catalog', $tenant))
            ->assertRedirect();

        $this->assertSame($afterFirst, $this->tenantProductCount($tenant));
    }

    public function test_master_catalog_sync_stops_at_product_limit(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $tenant = Tenant::query()->firstOrFail();

        $existing = $this->tenantProductCount($tenant);
        $plan = $tenant->activeSubscription?->plan;
        $this->assertNotNull($plan);
        $plan->limits = ['max_products' => $existing + 1];
        $plan->save();
        $tenant->unsetRelation('activeSubscription');
        $tenant->refresh();
        $tenant->load('activeSubscription.plan');
        $this->assertSame($existing + 1, TenantLimits::maxProducts($tenant));
        $this->assertSame(1, TenantLimits::remainingProductsForTenant($tenant));

        MasterProduct::query()->create([
            'name' => 'Limit Med One',
            'sku' => 'MSTR-LIMIT-1',
            'product_type' => 'tablet',
            'base_unit' => 'strip',
            'mrp' => 10,
            'default_purchase_price' => 8,
            'is_active' => true,
        ]);
        MasterProduct::query()->create([
            'name' => 'Limit Med Two',
            'sku' => 'MSTR-LIMIT-2',
            'product_type' => 'tablet',
            'base_unit' => 'strip',
            'mrp' => 11,
            'default_purchase_price' => 9,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('platform.tenants.master-catalog', $tenant))
            ->assertRedirect();

        $this->assertSame($existing + 1, $this->tenantProductCount($tenant));
        $this->assertTrue(MasterCatalogSyncStatus::raw($tenant->fresh())['stopped_at_limit'] ?? false);
    }

    public function test_pharmacy_owner_cannot_trigger_master_catalog_sync(): void
    {
        $this->seed(DatabaseSeeder::class);
        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $tenant = Tenant::query()->firstOrFail();

        $this->actingAs($owner)
            ->post(route('platform.tenants.master-catalog', $tenant))
            ->assertAppDialogRedirect();
    }

    private function tenantProductCount(Tenant $tenant): int
    {
        return Product::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenant->getKey())
            ->count();
    }
}
