<?php

namespace Tests\Feature\Catalog;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductStrengthTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_can_store_optional_strength(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($user)->post('/products', [
            'name' => 'Napa Extend',
            'generic_name' => 'Paracetamol',
            'strength' => '500 mg',
            'product_type' => 'tablet',
            'base_unit' => 'strip',
            'units' => [
                ['sell_unit' => 'strip', 'conversion_factor' => 1, 'purchase_price' => 20, 'sale_price' => 35, 'is_default' => true],
            ],
            'min_stock' => 0,
            'is_active' => true,
        ])->assertRedirect(route('tenant.products.index'));

        $this->assertDatabaseHas('products', [
            'name' => 'Napa Extend',
            'generic_name' => 'Paracetamol',
            'strength' => '500 mg',
        ]);
    }

    public function test_product_index_search_matches_strength(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($user)->post('/products', [
            'name' => 'Ace Plus',
            'generic_name' => 'Paracetamol',
            'strength' => '500 mg',
            'product_type' => 'tablet',
            'base_unit' => 'strip',
            'units' => [
                ['sell_unit' => 'strip', 'conversion_factor' => 1, 'purchase_price' => 10, 'sale_price' => 15, 'is_default' => true],
            ],
            'min_stock' => 0,
            'is_active' => true,
        ])->assertRedirect();

        $this->actingAs($user)
            ->get(route('tenant.products.index', ['q' => '500 mg']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('products.data', fn ($rows) => collect($rows)->contains('name', 'Ace Plus')));
    }
}
