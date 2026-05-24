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
