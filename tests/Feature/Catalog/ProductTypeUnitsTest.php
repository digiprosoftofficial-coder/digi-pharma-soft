<?php

namespace Tests\Feature\Catalog;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTypeUnitsTest extends TestCase
{
    use RefreshDatabase;

    public function test_syrup_product_cannot_use_strip_as_base_unit(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($user)
            ->from(route('tenant.products.create'))
            ->post('/products', [
                'name' => 'Test Syrup',
                'product_type' => 'syrup',
                'base_unit' => 'strip',
                'units' => [
                    ['sell_unit' => 'strip', 'conversion_factor' => 1, 'purchase_price' => 10, 'sale_price' => 15, 'is_default' => true],
                ],
                'min_stock' => 0,
                'is_active' => true,
            ])
            ->assertRedirect(route('tenant.products.create'))
            ->assertSessionHasErrors('base_unit');
    }

    public function test_syrup_product_can_use_piece_as_base_unit(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($user)->post('/products', [
            'name' => 'Tusca Syrup',
            'product_type' => 'syrup',
            'base_unit' => 'piece',
            'units' => [
                ['sell_unit' => 'piece', 'conversion_factor' => 1, 'purchase_price' => 80, 'sale_price' => 120, 'is_default' => true],
            ],
            'min_stock' => 0,
            'is_active' => true,
        ])->assertRedirect(route('tenant.products.index'));

        $this->assertDatabaseHas('products', [
            'name' => 'Tusca Syrup',
            'product_type' => 'syrup',
            'base_unit' => 'piece',
        ]);
    }

    public function test_tablet_product_can_use_strip_as_base_unit(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($user)->post('/products', [
            'name' => 'Napa Tablet',
            'product_type' => 'tablet',
            'base_unit' => 'strip',
            'units' => [
                ['sell_unit' => 'strip', 'conversion_factor' => 1, 'purchase_price' => 20, 'sale_price' => 35, 'is_default' => true],
            ],
            'min_stock' => 0,
            'is_active' => true,
        ])->assertRedirect(route('tenant.products.index'));

        $this->assertDatabaseHas('products', [
            'name' => 'Napa Tablet',
            'base_unit' => 'strip',
        ]);
    }
}
