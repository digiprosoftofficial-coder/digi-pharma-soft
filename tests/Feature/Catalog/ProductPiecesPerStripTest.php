<?php

namespace Tests\Feature\Catalog;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPiecesPerStripTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_saves_pieces_per_strip_and_syncs_piece_unit(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($user)->post('/products', [
            'name' => 'Vitamin C',
            'sku' => 'VIT-C',
            'product_type' => 'tablet',
            'base_unit' => 'strip',
            'pieces_per_strip' => 10,
            'units' => [
                ['sell_unit' => 'strip', 'conversion_factor' => 1, 'purchase_price' => 50, 'sale_price' => 70, 'is_default' => true],
                ['sell_unit' => 'box', 'conversion_factor' => 10, 'purchase_price' => 450, 'sale_price' => 650, 'is_default' => false],
            ],
            'min_stock' => 0,
            'is_active' => true,
        ])->assertRedirect();

        $product = Product::query()->where('sku', 'VIT-C')->firstOrFail();
        $this->assertSame('10.0000', (string) $product->pieces_per_strip);

        $piece = ProductUnit::query()
            ->where('product_id', $product->getKey())
            ->where('sell_unit', 'piece')
            ->firstOrFail();

        $this->assertEqualsWithDelta(0.1, (float) $piece->conversion_factor, 0.0001);
    }

    public function test_existing_product_can_update_pieces_per_strip_via_form_payload(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();
        $product->load('units');

        $this->actingAs($user)->put("/products/{$product->getKey()}", [
            'name' => $product->name,
            'sku' => $product->sku,
            'product_type' => $product->product_type,
            'base_unit' => $product->base_unit,
            'pieces_per_strip' => 12,
            'min_stock' => $product->min_stock,
            'is_active' => $product->is_active,
            'units' => $product->units->map(fn ($u) => [
                'sell_unit' => $u->sell_unit,
                'conversion_factor' => (float) $u->conversion_factor,
                'purchase_price' => (float) $u->purchase_price,
                'sale_price' => (float) $u->sale_price,
                'is_default' => (bool) $u->is_default,
            ])->all(),
        ])->assertRedirect(route('tenant.products.show', $product));

        $product->refresh();
        $this->assertSame('12.0000', (string) $product->pieces_per_strip);
    }

    public function test_product_show_includes_stock_pieces(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();

        $product->update(['pieces_per_strip' => 10]);

        $this->actingAs($user)
            ->get("/products/{$product->getKey()}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('stockPieces', fn ($v) => $v !== null && (float) $v > 0));
    }
}
