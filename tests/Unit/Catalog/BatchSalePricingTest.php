<?php

namespace Tests\Unit\Catalog;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductBatch;
use App\Support\Catalog\BatchSalePricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatchSalePricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_suggested_price_uses_markup_on_base_cost(): void
    {
        $this->seed();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();
        $product->update(['default_markup_percent' => 20]);

        $batch = ProductBatch::query()->where('product_id', $product->getKey())->firstOrFail();
        $batch->update(['purchase_unit_cost' => 50]);

        $suggested = BatchSalePricing::suggestedUnitPrice($batch, $product, 'strip');

        $this->assertSame(60.0, $suggested);
    }

    public function test_unit_cost_normalizes_pack_purchase_to_base(): void
    {
        $this->seed();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();

        $batch = ProductBatch::query()->create([
            'tenant_id' => $product->tenant_id,
            'product_id' => $product->getKey(),
            'batch_no' => 'PACK-COST',
            'quantity_on_hand' => 12,
            'purchase_unit_cost' => 120,
            'pack_sell_unit' => 'box',
            'pack_conversion_factor' => 12,
        ]);

        $this->assertSame(10.0, BatchSalePricing::costPerBaseUnit($batch));
        $this->assertSame(120.0, BatchSalePricing::unitCostInSellUnit($batch, $product, 'box'));
    }

    public function test_batch_markup_overrides_product_default(): void
    {
        $this->seed();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();
        $product->update(['default_markup_percent' => 10]);

        $batch = ProductBatch::query()->where('product_id', $product->getKey())->firstOrFail();
        $batch->update(['purchase_unit_cost' => 100, 'markup_percent' => 25]);

        $this->assertSame(25.0, BatchSalePricing::resolveMarkupPercent($product, $batch));
        $this->assertSame(125.0, BatchSalePricing::suggestedUnitPrice($batch, $product, 'strip'));
    }

    public function test_batch_sale_price_in_sell_unit(): void
    {
        $this->seed();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();

        $batch = ProductBatch::query()->create([
            'tenant_id' => $product->tenant_id,
            'product_id' => $product->getKey(),
            'batch_no' => 'MRP-UNIT',
            'quantity_on_hand' => 10,
            'purchase_unit_cost' => 10,
            'sale_price' => 25,
        ]);

        $this->assertSame(25.0, BatchSalePricing::batchSalePriceInSellUnit($batch, $product, 'strip'));
    }
}
