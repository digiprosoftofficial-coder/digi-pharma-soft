<?php

namespace Tests\Feature\Catalog;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductBatch;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use App\Support\Catalog\ProductListPagination;
use App\Support\Tenant\TenantContext;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductListPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_index_defaults_to_25_per_page(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($owner)
            ->get(route('tenant.products.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.per_page', ProductListPagination::DEFAULT)
                ->has('perPageOptions'));
    }

    public function test_product_index_respects_per_page_query(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($owner)
            ->get(route('tenant.products.index', ['per_page' => 50]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('filters.per_page', 50));
    }

    public function test_product_index_search_matches_generic_name(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($owner)->post('/products', [
            'name' => 'Napa Extend',
            'generic_name' => 'Paracetamol',
            'product_type' => 'tablet',
            'base_unit' => 'piece',
            'units' => [
                ['sell_unit' => 'piece', 'conversion_factor' => 1, 'purchase_price' => 1, 'sale_price' => 2, 'is_default' => true],
            ],
            'min_stock' => 0,
            'is_active' => true,
        ])->assertRedirect();

        $this->actingAs($owner)
            ->get(route('tenant.products.index', ['q' => 'Paracetamol']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('products.data')
                ->where('products.data', fn ($rows) => collect($rows)->contains('name', 'Napa Extend')));

        $this->actingAs($owner)
            ->get(route('tenant.products.index', ['q' => 'Ibuprofen']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('products.data', 0));
    }

    public function test_invalid_per_page_falls_back_to_default(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($owner)
            ->get(route('tenant.products.index', ['per_page' => 999]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('filters.per_page', ProductListPagination::DEFAULT));
    }

    public function test_product_index_filters_by_stock_status(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();
        app(TenantContext::class)->set(Tenant::query()->findOrFail($owner->tenant_id));
        $this->actingAs($owner);

        $inStock = Product::query()->create([
            'name' => 'In Stock Medicine',
            'sku' => 'STOCK-IN-1',
            'product_type' => 'tablet',
            'base_unit' => 'piece',
            'min_stock' => 0,
            'is_active' => true,
        ]);
        ProductBatch::query()->create([
            'product_id' => $inStock->getKey(),
            'batch_no' => 'STOCK-1',
            'quantity_on_hand' => 12,
            'purchase_unit_cost' => 1,
        ]);

        $outOfStock = Product::query()->create([
            'name' => 'Out Of Stock Medicine',
            'sku' => 'STOCK-OUT-1',
            'product_type' => 'tablet',
            'base_unit' => 'piece',
            'min_stock' => 0,
            'is_active' => true,
        ]);
        ProductBatch::query()->create([
            'product_id' => $outOfStock->getKey(),
            'batch_no' => 'STOCK-0',
            'quantity_on_hand' => 0,
            'purchase_unit_cost' => 1,
        ]);

        $this->get(route('tenant.products.index', ['stock' => 'in_stock', 'q' => 'Stock Medicine']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.stock', 'in_stock')
                ->where('products.data', fn ($rows) => collect($rows)->contains('name', 'In Stock Medicine')
                    && ! collect($rows)->contains('name', 'Out Of Stock Medicine')));

        $this->get(route('tenant.products.index', ['stock' => 'out_of_stock', 'q' => 'Stock Medicine']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.stock', 'out_of_stock')
                ->where('products.data', fn ($rows) => collect($rows)->contains('name', 'Out Of Stock Medicine')
                    && ! collect($rows)->contains('name', 'In Stock Medicine')));
    }
}
