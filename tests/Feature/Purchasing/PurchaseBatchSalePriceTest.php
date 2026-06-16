<?php

namespace Tests\Feature\Purchasing;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductBatch;
use App\Domain\Purchasing\Models\Supplier;
use App\Models\User;
use App\Support\Catalog\BatchSalePricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseBatchSalePriceTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_saves_sale_price_on_batch_for_pos(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();
        $supplier = Supplier::query()->firstOrFail();

        $this->actingAs($user)->post('/purchases', [
            'supplier_id' => $supplier->getKey(),
            'invoice_no' => 'BATCH-MRP-1',
            'purchased_at' => now()->toDateString(),
            'paid' => 0,
            'lines' => [[
                'product_id' => $product->getKey(),
                'batch_no' => 'LOT-MRP-1',
                'quantity' => 5,
                'sell_unit' => 'strip',
                'unit_cost' => 12,
                'sale_price' => 18.5,
            ]],
        ])->assertRedirect();

        $batch = ProductBatch::query()
            ->where('product_id', $product->getKey())
            ->where('batch_no', 'LOT-MRP-1')
            ->firstOrFail();

        $this->assertSame('18.5000', (string) $batch->sale_price);
        $this->assertSame(18.5, BatchSalePricing::batchSalePriceInSellUnit($batch, $product, 'strip'));
    }

    public function test_batch_sale_price_wins_over_markup_in_pricing_helper(): void
    {
        $this->seed();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();
        $product->update(['default_markup_percent' => 50]);

        $batch = ProductBatch::query()->create([
            'tenant_id' => $product->tenant_id,
            'product_id' => $product->getKey(),
            'batch_no' => 'MRP-WINS',
            'quantity_on_hand' => 10,
            'purchase_unit_cost' => 10,
            'sale_price' => 22,
        ]);

        $batchPrice = BatchSalePricing::batchSalePriceInSellUnit($batch, $product, 'strip');
        $markupPrice = BatchSalePricing::suggestedUnitPrice($batch, $product, 'strip');

        $this->assertSame(22.0, $batchPrice);
        $this->assertSame(15.0, $markupPrice);
        $this->assertNotSame($markupPrice, $batchPrice);
    }

    public function test_batch_sale_price_converts_pack_unit_to_base(): void
    {
        $this->seed();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();

        $batch = ProductBatch::query()->create([
            'tenant_id' => $product->tenant_id,
            'product_id' => $product->getKey(),
            'batch_no' => 'PACK-MRP',
            'quantity_on_hand' => 12,
            'purchase_unit_cost' => 120,
            'sale_price' => 180,
            'pack_sell_unit' => 'box',
            'pack_conversion_factor' => 12,
        ]);

        $this->assertSame(15.0, BatchSalePricing::salePricePerBaseUnit($batch));
        $this->assertSame(180.0, BatchSalePricing::batchSalePriceInSellUnit($batch, $product, 'box'));
        $this->assertSame(15.0, BatchSalePricing::batchSalePriceInSellUnit($batch, $product, 'strip'));
    }

    public function test_product_edit_can_update_batch_sale_price_and_markup(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();
        $batch = ProductBatch::query()->where('product_id', $product->getKey())->firstOrFail();

        $editUrl = "/products/{$product->getKey()}/edit";

        $this->actingAs($user)
            ->from($editUrl)
            ->patch("/products/{$product->getKey()}/batches/{$batch->getKey()}/markup", [
                'sale_price' => 42.5,
                'markup_percent' => 20,
            ])
            ->assertRedirect($editUrl);

        $batch->refresh();
        $this->assertSame('42.5000', (string) $batch->sale_price);
        $this->assertSame('20.00', (string) $batch->markup_percent);
    }
}
