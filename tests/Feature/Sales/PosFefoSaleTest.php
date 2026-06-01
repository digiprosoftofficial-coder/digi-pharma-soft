<?php

namespace Tests\Feature\Sales;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductBatch;
use App\Domain\Sales\Models\Sale;
use App\Domain\Sales\Models\SaleLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosFefoSaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_pos_sale_splits_quantity_across_batches_fefo(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();

        $existing = ProductBatch::query()->where('product_id', $product->getKey())->firstOrFail();
        $existing->update(['quantity_on_hand' => 0]);

        $batchA = $this->makeBatch($product, [
            'batch_no' => 'FEFO-A',
            'expiry_date' => now()->addMonth()->toDateString(),
            'quantity_on_hand' => 5,
        ]);
        $batchB = $this->makeBatch($product, [
            'batch_no' => 'FEFO-B',
            'expiry_date' => now()->addMonths(3)->toDateString(),
            'quantity_on_hand' => 10,
        ]);

        $this->actingAs($user)->post('/pos/sales', [
            'lines' => [[
                'product_batch_id' => $batchB->getKey(),
                'quantity' => 12,
                'sell_unit' => 'strip',
                'unit_price' => 10,
            ]],
            'payments' => [['method' => 'cash', 'amount' => 120]],
        ])->assertRedirect();

        $batchA->refresh();
        $batchB->refresh();
        $this->assertSame(3.0, (float) $batchA->quantity_on_hand);
        $this->assertSame(0.0, (float) $batchB->quantity_on_hand);

        $sale = Sale::query()->latest('id')->firstOrFail();
        $lines = SaleLine::query()->where('sale_id', $sale->getKey())->orderBy('id')->get();
        $this->assertCount(2, $lines);
        $this->assertSame((int) $batchB->getKey(), (int) $lines[0]->product_batch_id);
        $this->assertSame(10.0, (float) $lines[0]->quantity_base);
        $this->assertSame((int) $batchA->getKey(), (int) $lines[1]->product_batch_id);
        $this->assertSame(2.0, (float) $lines[1]->quantity_base);
    }

    public function test_product_search_orders_batches_by_expiry(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();

        ProductBatch::query()->where('product_id', $product->getKey())->delete();

        $this->makeBatch($product, [
            'batch_no' => 'SEARCH-LATE',
            'expiry_date' => now()->addYear()->toDateString(),
            'quantity_on_hand' => 1,
        ]);
        $this->makeBatch($product, [
            'batch_no' => 'SEARCH-SOON',
            'expiry_date' => now()->addWeek()->toDateString(),
            'quantity_on_hand' => 1,
        ]);

        $response = $this->actingAs($user)->getJson('/catalog/product-search?q=Paracetamol');
        $response->assertOk();

        $item = collect($response->json('data'))->firstWhere('sku', 'PAR-500');
        $this->assertNotNull($item);
        $this->assertSame('SEARCH-SOON', $item['batches'][0]['batch_no']);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeBatch(Product $product, array $attributes): ProductBatch
    {
        return ProductBatch::query()->create(array_merge([
            'tenant_id' => $product->tenant_id,
            'product_id' => $product->getKey(),
            'purchase_unit_cost' => 1,
        ], $attributes));
    }
}
