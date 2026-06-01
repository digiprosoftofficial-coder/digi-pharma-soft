<?php

namespace Tests\Feature\Sales;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductBatch;
use App\Domain\Sales\Models\SaleLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosBatchPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_pos_sale_stores_unit_cost_at_sale_from_batch(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();
        $product->update(['default_markup_percent' => 15]);

        $batch = ProductBatch::query()->where('product_id', $product->getKey())->firstOrFail();
        $batch->update(['quantity_on_hand' => 100, 'purchase_unit_cost' => 40]);

        $this->actingAs($user)->post('/pos/sales', [
            'lines' => [[
                'product_batch_id' => $batch->getKey(),
                'quantity' => 2,
                'sell_unit' => 'strip',
                'unit_price' => 50,
            ]],
            'payments' => [['method' => 'cash', 'amount' => 100]],
        ])->assertRedirect();

        $line = SaleLine::query()->latest('id')->firstOrFail();
        $this->assertSame(40.0, (float) $line->unit_cost_at_sale);
        $this->assertSame(50.0, (float) $line->unit_price);
    }

    public function test_product_can_set_default_markup_percent(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($user)->post('/products', [
            'name' => 'Markup Test Product',
            'product_type' => 'syrup',
            'base_unit' => 'piece',
            'default_markup_percent' => 18,
            'units' => [[
                'sell_unit' => 'piece',
                'conversion_factor' => 1,
                'purchase_price' => 10,
                'sale_price' => 15,
                'is_default' => true,
            ]],
            'is_active' => true,
        ])->assertRedirect();

        $product = Product::query()->where('name', 'Markup Test Product')->firstOrFail();
        $this->assertSame('18.00', (string) $product->default_markup_percent);
    }
}
