<?php

namespace Tests\Feature\Catalog;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductBatch;
use App\Domain\Tenant\Models\Branch;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use App\Support\Catalog\ProductCatalogStats;
use App\Support\Tenant\TenantContext;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCatalogStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_and_product_list_include_medicine_counts(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();
        app(TenantContext::class)->set(Tenant::query()->findOrFail($owner->tenant_id));
        $this->actingAs($owner);

        $outOfStock = Product::query()->create([
            'name' => 'Zero Stock Medicine',
            'sku' => 'STOCK-ZERO-1',
            'product_type' => 'tablet',
            'base_unit' => 'piece',
            'min_stock' => 0,
            'is_active' => true,
        ]);
        ProductBatch::query()->create([
            'product_id' => $outOfStock->getKey(),
            'batch_no' => 'ZERO-1',
            'quantity_on_hand' => 0,
            'purchase_unit_cost' => 1,
        ]);

        $expectedTotal = (int) Product::query()->count();
        $expectedActive = (int) Product::query()->where('is_active', true)->count();
        $expectedInStock = $expectedTotal - 1;

        $this->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Tenant/Dashboard')
                ->where('kpis.productCount', $expectedTotal)
                ->where('kpis.activeProductCount', $expectedActive)
                ->where('kpis.inStockProductCount', $expectedInStock)
                ->where('kpis.outOfStockProductCount', 1)
                ->where('branchPerformance.0.products_in_stock', $expectedInStock));

        $this->get(route('tenant.products.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Catalog/Products/Index')
                ->where('productStats.total', $expectedTotal)
                ->where('productStats.active', $expectedActive)
                ->where('productStats.in_stock', $expectedInStock)
                ->where('productStats.out_of_stock', 1)
                ->has('productStats.by_branch', 1)
                ->where('productStats.by_branch.0.in_stock', $expectedInStock));
    }

    public function test_in_stock_counts_are_distinct_skus_per_branch(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $tenant = Tenant::query()->findOrFail($owner->tenant_id);
        app(TenantContext::class)->set($tenant);

        $defaultBranch = Branch::query()->where('is_default', true)->firstOrFail();
        $secondBranch = Branch::query()->create([
            'name' => 'Uttara',
            'code' => 'UTT',
            'is_active' => true,
            'is_default' => false,
        ]);

        $shared = Product::query()->create([
            'name' => 'Shared Stock Medicine',
            'sku' => 'SHARED-STK-1',
            'product_type' => 'tablet',
            'base_unit' => 'piece',
            'min_stock' => 0,
            'is_active' => true,
        ]);
        ProductBatch::query()->create([
            'branch_id' => $defaultBranch->getKey(),
            'product_id' => $shared->getKey(),
            'batch_no' => 'SHARED-A',
            'quantity_on_hand' => 4,
            'purchase_unit_cost' => 1,
        ]);
        ProductBatch::query()->create([
            'branch_id' => $secondBranch->getKey(),
            'product_id' => $shared->getKey(),
            'batch_no' => 'SHARED-B',
            'quantity_on_hand' => 7,
            'purchase_unit_cost' => 1,
        ]);

        $stats = ProductCatalogStats::forTenant((int) $tenant->getKey());
        $byBranch = collect($stats['by_branch'])->keyBy('id');

        $this->assertSame(5, $stats['in_stock']);
        $this->assertSame(5, $byBranch[$defaultBranch->getKey()]['in_stock']);
        $this->assertSame(1, $byBranch[$secondBranch->getKey()]['in_stock']);
        $this->assertSame(
            1,
            ProductCatalogStats::forTenant((int) $tenant->getKey(), (int) $secondBranch->getKey())['in_stock'],
        );

        $this->actingAs($owner)
            ->post('/branches/switch', ['branch_id' => $secondBranch->getKey()])
            ->assertRedirect();

        $this->get(route('tenant.products.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Catalog/Products/Index')
                ->where('productStats.in_stock', 1));

        $this->get(route('tenant.products.index', ['stock' => 'in_stock']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Catalog/Products/Index')
                ->where('productStats.in_stock', 1)
                ->has('products.data', 1)
                ->where('products.data.0.sku', 'SHARED-STK-1'));
    }
}
