<?php

namespace Tests\Feature\Purchasing;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductBatch;
use App\Domain\Purchasing\Models\Supplier;
use App\Domain\Sales\Models\SaleLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchasePackSizeTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_box_with_custom_strips_per_box_updates_stock(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();
        $supplier = Supplier::query()->firstOrFail();

        $this->actingAs($user)->post('/purchases', [
            'supplier_id' => $supplier->getKey(),
            'invoice_no' => 'INV-PACK-12',
            'purchased_at' => now()->toDateString(),
            'paid' => 0,
            'lines' => [[
                'product_id' => $product->getKey(),
                'batch_no' => 'BOX-12-PACK',
                'expiry_date' => '2028-06-01',
                'quantity' => 2,
                'sell_unit' => 'box',
                'conversion_factor' => 12,
                'unit_cost' => 400,
            ]],
        ])->assertRedirect(route('tenant.purchases.index'));

        $this->assertDatabaseHas('purchase_lines', [
            'batch_no' => 'BOX-12-PACK',
            'conversion_factor' => '12.0000',
            'quantity_base' => '24.0000',
        ]);

        $batch = ProductBatch::query()
            ->where('product_id', $product->getKey())
            ->where('batch_no', 'BOX-12-PACK')
            ->firstOrFail();

        $this->assertSame('box', $batch->pack_sell_unit);
        $this->assertSame('12.0000', (string) $batch->pack_conversion_factor);
        $this->assertSame('24.0000', (string) $batch->quantity_on_hand);
    }

    public function test_pos_sale_from_batch_uses_batch_pack_size_for_box(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();
        $supplier = Supplier::query()->firstOrFail();

        $this->actingAs($user)->post('/purchases', [
            'supplier_id' => $supplier->getKey(),
            'invoice_no' => 'INV-PACK-6',
            'purchased_at' => now()->toDateString(),
            'paid' => 0,
            'lines' => [[
                'product_id' => $product->getKey(),
                'batch_no' => 'BOX-6-PACK',
                'quantity' => 1,
                'sell_unit' => 'box',
                'conversion_factor' => 6,
                'unit_cost' => 200,
            ]],
        ]);

        $batch = ProductBatch::query()
            ->where('batch_no', 'BOX-6-PACK')
            ->firstOrFail();

        $this->actingAs($user)->post('/pos/sales', [
            'lines' => [[
                'product_batch_id' => $batch->getKey(),
                'quantity' => 1,
                'sell_unit' => 'box',
                'unit_price' => 350,
            ]],
            'payments' => [['method' => 'cash', 'amount' => 350]],
            'discount' => 0,
            'tax' => 0,
        ])->assertRedirect();

        $saleLine = SaleLine::query()->latest('id')->firstOrFail();
        $this->assertSame('6.0000', (string) $saleLine->quantity_base);
        $this->assertSame('6.0000', (string) $saleLine->conversion_factor);
        $this->assertSame('0.0000', (string) $batch->fresh()->quantity_on_hand);
    }

    public function test_purchase_carton_with_custom_boxes_per_carton_updates_stock(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();
        $product->units()->create([
            'sell_unit' => 'carton',
            'conversion_factor' => 120,
            'purchase_price' => 5000,
            'sale_price' => 7000,
            'is_default' => false,
            'sort_order' => 2,
        ]);
        $product->update(['boxes_per_carton' => 12]);
        $supplier = Supplier::query()->firstOrFail();

        $this->actingAs($user)->post('/purchases', [
            'supplier_id' => $supplier->getKey(),
            'invoice_no' => 'INV-CTN-5',
            'purchased_at' => now()->toDateString(),
            'paid' => 0,
            'lines' => [[
                'product_id' => $product->getKey(),
                'batch_no' => 'CTN-5-BOX',
                'quantity' => 2,
                'sell_unit' => 'carton',
                'conversion_factor' => 50,
                'unit_cost' => 2500,
            ]],
        ])->assertRedirect(route('tenant.purchases.index'));

        $batch = ProductBatch::query()
            ->where('product_id', $product->getKey())
            ->where('batch_no', 'CTN-5-BOX')
            ->firstOrFail();

        $this->assertSame('carton', $batch->pack_sell_unit);
        $this->assertSame('50.0000', (string) $batch->pack_conversion_factor);
        $this->assertSame('100.0000', (string) $batch->quantity_on_hand);
    }

    public function test_pos_sale_from_batch_uses_batch_pack_size_for_carton(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();
        $product->units()->create([
            'sell_unit' => 'carton',
            'conversion_factor' => 120,
            'purchase_price' => 5000,
            'sale_price' => 7000,
            'is_default' => false,
            'sort_order' => 2,
        ]);
        $supplier = Supplier::query()->firstOrFail();

        $this->actingAs($user)->post('/purchases', [
            'supplier_id' => $supplier->getKey(),
            'invoice_no' => 'INV-CTN-POS',
            'purchased_at' => now()->toDateString(),
            'paid' => 0,
            'lines' => [[
                'product_id' => $product->getKey(),
                'batch_no' => 'CTN-5-POS',
                'quantity' => 1,
                'sell_unit' => 'carton',
                'conversion_factor' => 50,
                'unit_cost' => 2500,
            ]],
        ]);

        $batch = ProductBatch::query()->where('batch_no', 'CTN-5-POS')->firstOrFail();

        $this->actingAs($user)->post('/pos/sales', [
            'lines' => [[
                'product_batch_id' => $batch->getKey(),
                'quantity' => 1,
                'sell_unit' => 'carton',
                'unit_price' => 7000,
            ]],
            'payments' => [['method' => 'cash', 'amount' => 7000]],
            'discount' => 0,
            'tax' => 0,
        ])->assertRedirect();

        $saleLine = SaleLine::query()->latest('id')->firstOrFail();
        $this->assertSame('50.0000', (string) $saleLine->quantity_base);
        $this->assertSame('50.0000', (string) $saleLine->conversion_factor);
    }
}
