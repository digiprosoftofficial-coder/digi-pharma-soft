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

    public function test_tablet_conversions_auto_create_piece_box_and_carton_units(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($user)->post('/products', [
            'name' => 'Hierarchy Tablet',
            'product_type' => 'tablet',
            'base_unit' => 'strip',
            'pieces_per_strip' => 10,
            'strips_per_box' => 10,
            'boxes_per_carton' => 12,
            'units' => [
                ['sell_unit' => 'strip', 'conversion_factor' => 1, 'purchase_price' => 20, 'sale_price' => 35, 'is_default' => true],
            ],
            'min_stock' => 0,
            'is_active' => true,
        ])->assertRedirect(route('tenant.products.index'));

        $product = \App\Domain\Catalog\Models\Product::query()->where('name', 'Hierarchy Tablet')->firstOrFail();
        $product->load('units');

        $this->assertSame('10.0000', (string) $product->pieces_per_strip);
        $this->assertSame('10.0000', (string) $product->strips_per_box);
        $this->assertSame('12.0000', (string) $product->boxes_per_carton);
        $this->assertNull($product->pieces_per_box);

        $this->assertTrue($product->units->contains('sell_unit', 'piece'));
        $this->assertTrue($product->units->contains('sell_unit', 'box'));
        $this->assertTrue($product->units->contains('sell_unit', 'carton'));
        $this->assertEqualsWithDelta(0.1, (float) $product->units->firstWhere('sell_unit', 'piece')->conversion_factor, 0.0001);
        $this->assertEqualsWithDelta(10.0, (float) $product->units->firstWhere('sell_unit', 'box')->conversion_factor, 0.0001);
        $this->assertEqualsWithDelta(120.0, (float) $product->units->firstWhere('sell_unit', 'carton')->conversion_factor, 0.0001);
    }

    public function test_piece_base_product_uses_pieces_per_box_hierarchy(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($user)->post('/products', [
            'name' => 'Piece Syrup Box Pack',
            'product_type' => 'syrup',
            'base_unit' => 'piece',
            'pieces_per_box' => 24,
            'boxes_per_carton' => 6,
            'units' => [
                ['sell_unit' => 'piece', 'conversion_factor' => 1, 'purchase_price' => 50, 'sale_price' => 80, 'is_default' => true],
            ],
            'min_stock' => 0,
            'is_active' => true,
        ])->assertRedirect(route('tenant.products.index'));

        $product = \App\Domain\Catalog\Models\Product::query()->where('name', 'Piece Syrup Box Pack')->firstOrFail();
        $product->load('units');

        $this->assertSame('24.0000', (string) $product->pieces_per_box);
        $this->assertNull($product->strips_per_box);
        $this->assertTrue($product->units->contains('sell_unit', 'box'));
        $this->assertTrue($product->units->contains('sell_unit', 'carton'));
        $this->assertEqualsWithDelta(24.0, (float) $product->units->firstWhere('sell_unit', 'box')->conversion_factor, 0.0001);
        $this->assertEqualsWithDelta(144.0, (float) $product->units->firstWhere('sell_unit', 'carton')->conversion_factor, 0.0001);
    }

    public function test_duplicate_sell_units_are_rejected(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($user)
            ->from(route('tenant.products.create'))
            ->post('/products', [
                'name' => 'Duplicate Units Product',
                'product_type' => 'tablet',
                'base_unit' => 'strip',
                'units' => [
                    ['sell_unit' => 'strip', 'conversion_factor' => 1, 'purchase_price' => 20, 'sale_price' => 35, 'is_default' => true],
                    ['sell_unit' => 'piece', 'conversion_factor' => 0.1, 'purchase_price' => 2, 'sale_price' => 3.5, 'is_default' => false],
                    ['sell_unit' => 'piece', 'conversion_factor' => 0.1, 'purchase_price' => 2, 'sale_price' => 3.5, 'is_default' => false],
                ],
                'min_stock' => 0,
                'is_active' => true,
            ])
            ->assertRedirect(route('tenant.products.create'))
            ->assertSessionHasErrors('units.2.sell_unit');
    }
}
