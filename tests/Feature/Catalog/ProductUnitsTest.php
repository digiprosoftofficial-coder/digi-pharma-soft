<?php

namespace Tests\Feature\Catalog;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductBatch;
use App\Domain\Sales\Models\SaleLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductUnitsTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_can_be_created_with_multiple_sell_units(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $response = $this->actingAs($user)->post('/products', [
            'name' => 'Amoxicillin 500mg',
            'sku' => 'AMX-500',
            'product_type' => 'capsule',
            'base_unit' => 'strip',
            'units' => [
                ['sell_unit' => 'strip', 'conversion_factor' => 1, 'purchase_price' => 50, 'sale_price' => 70, 'is_default' => true],
                ['sell_unit' => 'box', 'conversion_factor' => 10, 'purchase_price' => 450, 'sale_price' => 650, 'is_default' => false],
            ],
            'min_stock' => 5,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('tenant.products.index'));

        $product = Product::query()->where('sku', 'AMX-500')->firstOrFail();
        $this->assertSame('strip', $product->base_unit);
        $this->assertCount(2, $product->units);
    }

    public function test_pos_sale_deducts_stock_in_base_units_when_selling_boxes(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();
        $batch = ProductBatch::query()->where('product_id', $product->getKey())->firstOrFail();
        $before = (float) $batch->quantity_on_hand;

        $this->actingAs($user)->post('/pos/sales', [
            'lines' => [[
                'product_batch_id' => $batch->getKey(),
                'quantity' => 2,
                'sell_unit' => 'box',
                'unit_price' => 320,
            ]],
            'payments' => [['method' => 'cash', 'amount' => 640]],
        ])->assertRedirect();

        $batch->refresh();
        $this->assertSame($before - 20.0, (float) $batch->quantity_on_hand);

        $line = SaleLine::query()->latest('id')->first();
        $this->assertSame('box', $line->sell_unit);
        $this->assertSame(20.0, (float) $line->quantity_base);
    }
}
