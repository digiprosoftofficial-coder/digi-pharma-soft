<?php

namespace Tests\Feature\Catalog;

use App\Domain\Catalog\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductStripsPerBoxTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_saves_strips_per_box_and_syncs_box_unit(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($user)->post('/products', [
            'name' => 'Amox Box Pack',
            'sku' => 'AMX-BOX',
            'product_type' => 'capsule',
            'base_unit' => 'strip',
            'strips_per_box' => 12,
            'units' => [
                ['sell_unit' => 'strip', 'conversion_factor' => 1, 'purchase_price' => 50, 'sale_price' => 70, 'is_default' => true],
                ['sell_unit' => 'box', 'conversion_factor' => 10, 'purchase_price' => 450, 'sale_price' => 650, 'is_default' => false],
            ],
            'min_stock' => 5,
            'is_active' => true,
        ])->assertRedirect(route('tenant.products.index'));

        $product = Product::query()->where('sku', 'AMX-BOX')->firstOrFail();
        $this->assertSame('12.0000', (string) $product->strips_per_box);

        $box = $product->units()->where('sell_unit', 'box')->first();
        $this->assertNotNull($box);
        $this->assertSame(12.0, (float) $box->conversion_factor);
    }

    public function test_existing_product_can_update_strips_per_box_via_form_payload(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();

        $units = $product->units->map(fn ($u) => [
            'sell_unit' => $u->sell_unit,
            'conversion_factor' => (float) $u->conversion_factor,
            'purchase_price' => $u->purchase_price,
            'sale_price' => $u->sale_price,
            'is_default' => (bool) $u->is_default,
        ])->all();

        $this->actingAs($user)->put("/products/{$product->getKey()}", [
            'strips_per_box' => 12,
            'units' => $units,
        ])->assertRedirect();

        $product->refresh();
        $this->assertSame('12.0000', (string) $product->strips_per_box);

        $box = $product->units()->where('sell_unit', 'box')->firstOrFail();
        $this->assertSame(12.0, (float) $box->conversion_factor);
    }
}
