<?php

namespace Tests\Feature\Catalog;

use App\Domain\Catalog\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductBoxesPerCartonTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_saves_boxes_per_carton_and_syncs_carton_unit(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($user)->post('/products', [
            'name' => 'Bulk Vitamin C',
            'sku' => 'VIT-BULK',
            'product_type' => 'tablet',
            'base_unit' => 'strip',
            'boxes_per_carton' => 12,
            'units' => [
                ['sell_unit' => 'strip', 'conversion_factor' => 1, 'purchase_price' => 50, 'sale_price' => 70, 'is_default' => true],
                ['sell_unit' => 'box', 'conversion_factor' => 10, 'purchase_price' => 450, 'sale_price' => 650, 'is_default' => false],
                ['sell_unit' => 'carton', 'conversion_factor' => 100, 'purchase_price' => 4000, 'sale_price' => 7000, 'is_default' => false],
            ],
            'min_stock' => 5,
            'is_active' => true,
        ])->assertRedirect(route('tenant.products.index'));

        $product = Product::query()->where('sku', 'VIT-BULK')->firstOrFail();
        $this->assertSame('12.0000', (string) $product->boxes_per_carton);

        $carton = $product->units()->where('sell_unit', 'carton')->first();
        $this->assertNotNull($carton);
        $this->assertSame(120.0, (float) $carton->conversion_factor);
    }

    public function test_existing_product_can_update_boxes_per_carton_via_form_payload(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();

        // Seeder already provides a box unit (12 strips per box) for PAR-500.
        $boxFactor = (float) $product->units()->where('sell_unit', 'box')->firstOrFail()->conversion_factor;

        $units = $product->units->map(fn ($u) => [
            'sell_unit' => $u->sell_unit,
            'conversion_factor' => (float) $u->conversion_factor,
            'purchase_price' => $u->purchase_price,
            'sale_price' => $u->sale_price,
            'is_default' => (bool) $u->is_default,
        ])->all();

        $this->actingAs($user)->put("/products/{$product->getKey()}", [
            'boxes_per_carton' => 10,
            'units' => $units,
        ])->assertRedirect();

        $product->refresh();
        $this->assertSame('10.0000', (string) $product->boxes_per_carton);

        // Carton conversion is derived from box conversion x boxes-per-carton.
        $carton = $product->units()->where('sell_unit', 'carton')->firstOrFail();
        $this->assertSame($boxFactor * 10.0, (float) $carton->conversion_factor);
    }
}
