<?php

namespace Tests\Feature\Catalog;

use App\Models\User;
use App\Support\Catalog\ProductListPagination;
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
}
