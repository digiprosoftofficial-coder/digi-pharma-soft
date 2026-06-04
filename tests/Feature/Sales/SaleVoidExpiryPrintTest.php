<?php

namespace Tests\Feature\Sales;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductBatch;
use App\Domain\Sales\Models\Sale;
use App\Domain\Sales\Models\SaleLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleVoidExpiryPrintTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_sell_expired_batch(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();

        ProductBatch::query()->where('product_id', $product->getKey())->delete();

        $expired = $this->makeBatch($product, [
            'batch_no' => 'EXP-OLD',
            'expiry_date' => now()->subDay()->toDateString(),
            'quantity_on_hand' => 10,
        ]);

        $this->actingAs($user)
            ->post('/pos/sales', [
                'lines' => [[
                    'product_batch_id' => $expired->getKey(),
                    'quantity' => 1,
                    'sell_unit' => 'strip',
                    'unit_price' => 10,
                ]],
                'payments' => [['method' => 'cash', 'amount' => 10]],
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('checkout');

        $expired->refresh();
        $this->assertSame(10.0, (float) $expired->quantity_on_hand);
    }

    public function test_product_search_excludes_expired_batches(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();

        ProductBatch::query()->where('product_id', $product->getKey())->delete();

        $this->makeBatch($product, [
            'batch_no' => 'EXP-HIDDEN',
            'expiry_date' => now()->subWeek()->toDateString(),
            'quantity_on_hand' => 5,
        ]);
        $ok = $this->makeBatch($product, [
            'batch_no' => 'EXP-OK',
            'expiry_date' => now()->addMonth()->toDateString(),
            'quantity_on_hand' => 3,
        ]);

        $response = $this->actingAs($user)->getJson('/catalog/product-search?q=Paracetamol');
        $response->assertOk();

        $item = collect($response->json('data'))->firstWhere('sku', 'PAR-500');
        $this->assertNotNull($item);
        $this->assertCount(1, $item['batches']);
        $this->assertSame('EXP-OK', $item['batches'][0]['batch_no']);
        $this->assertFalse($item['batches'][0]['is_expired']);
        $this->assertSame((int) $ok->getKey(), (int) $item['batches'][0]['id']);
    }

    public function test_pos_cart_discount_percent_applies_to_subtotal(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();

        ProductBatch::query()->where('product_id', $product->getKey())->delete();

        $batch = $this->makeBatch($product, [
            'batch_no' => 'DISC-10',
            'expiry_date' => now()->addMonths(6)->toDateString(),
            'quantity_on_hand' => 10,
        ]);

        $this->actingAs($user)->post('/pos/sales', [
            'lines' => [[
                'product_batch_id' => $batch->getKey(),
                'quantity' => 2,
                'sell_unit' => 'strip',
                'unit_price' => 100,
            ]],
            'payments' => [['method' => 'cash', 'amount' => 180]],
            'discount_percent' => 10,
        ])->assertRedirect();

        $sale = Sale::query()->latest('id')->firstOrFail();
        $this->assertSame(200.0, (float) $sale->subtotal);
        $this->assertSame(20.0, (float) $sale->discount);
        $this->assertSame(180.0, (float) $sale->total);
        $this->assertSame(180.0, (float) $sale->paid);
    }

    public function test_cash_overpayment_records_change_and_caps_paid(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();

        ProductBatch::query()->where('product_id', $product->getKey())->delete();

        $batch = $this->makeBatch($product, [
            'batch_no' => 'CHG-1',
            'expiry_date' => now()->addMonths(6)->toDateString(),
            'quantity_on_hand' => 10,
        ]);

        $this->actingAs($user)->post('/pos/sales', [
            'lines' => [[
                'product_batch_id' => $batch->getKey(),
                'quantity' => 2,
                'sell_unit' => 'strip',
                'unit_price' => 90,
            ]],
            'payments' => [['method' => 'cash', 'amount' => 500]],
        ])->assertRedirect();

        $sale = Sale::query()->latest('id')->firstOrFail();
        $this->assertSame(180.0, (float) $sale->total);
        $this->assertSame(500.0, (float) $sale->amount_tendered);
        $this->assertSame(180.0, (float) $sale->paid);
        $this->assertSame(320.0, (float) $sale->change_returned);
        $this->assertSame(0.0, (float) $sale->due);
    }

    public function test_partial_payment_without_customer_is_rejected(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();

        ProductBatch::query()->where('product_id', $product->getKey())->delete();

        $batch = $this->makeBatch($product, [
            'batch_no' => 'DUE-1',
            'expiry_date' => now()->addMonths(6)->toDateString(),
            'quantity_on_hand' => 10,
        ]);

        $this->actingAs($user)->post('/pos/sales', [
            'lines' => [[
                'product_batch_id' => $batch->getKey(),
                'quantity' => 2,
                'sell_unit' => 'strip',
                'unit_price' => 100,
            ]],
            'payments' => [['method' => 'cash', 'amount' => 120]],
        ])
            ->assertRedirect()
            ->assertSessionHasErrors('checkout');

        $this->assertSame(0, Sale::query()->count());
        $batch->refresh();
        $this->assertSame(10.0, (float) $batch->quantity_on_hand);
    }

    public function test_partial_payment_with_customer_records_due(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();

        ProductBatch::query()->where('product_id', $product->getKey())->delete();

        $batch = $this->makeBatch($product, [
            'batch_no' => 'DUE-2',
            'expiry_date' => now()->addMonths(6)->toDateString(),
            'quantity_on_hand' => 10,
        ]);

        $customer = \App\Domain\Sales\Models\Customer::query()->create([
            'tenant_id' => $product->tenant_id,
            'name' => 'Credit Buyer',
            'balance_due' => 0,
        ]);

        $this->actingAs($user)->post('/pos/sales', [
            'customer_id' => $customer->getKey(),
            'lines' => [[
                'product_batch_id' => $batch->getKey(),
                'quantity' => 2,
                'sell_unit' => 'strip',
                'unit_price' => 100,
            ]],
            'payments' => [['method' => 'cash', 'amount' => 120]],
        ])->assertRedirect();

        $sale = Sale::query()->latest('id')->firstOrFail();
        $this->assertSame(200.0, (float) $sale->total);
        $this->assertSame(120.0, (float) $sale->paid);
        $this->assertSame(80.0, (float) $sale->due);
        $this->assertSame(0.0, (float) $sale->change_returned);

        $customer->refresh();
        $this->assertSame(80.0, (float) $customer->balance_due);
    }

    public function test_void_sale_restores_stock(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();

        ProductBatch::query()->where('product_id', $product->getKey())->delete();

        $batch = $this->makeBatch($product, [
            'batch_no' => 'VOID-1',
            'expiry_date' => now()->addMonths(6)->toDateString(),
            'quantity_on_hand' => 5,
        ]);

        $this->actingAs($user)->post('/pos/sales', [
            'lines' => [[
                'product_batch_id' => $batch->getKey(),
                'quantity' => 2,
                'sell_unit' => 'strip',
                'unit_price' => 10,
            ]],
            'payments' => [['method' => 'cash', 'amount' => 20]],
        ])->assertRedirect();

        $batch->refresh();
        $this->assertSame(3.0, (float) $batch->quantity_on_hand);

        $sale = Sale::query()->latest('id')->firstOrFail();
        $this->assertSame('posted', $sale->status);

        $this->actingAs($user)
            ->post('/sales/'.$sale->getKey().'/void')
            ->assertRedirect(route('tenant.sales.index'));

        $sale->refresh();
        $batch->refresh();
        $this->assertSame('voided', $sale->status);
        $this->assertSame(5.0, (float) $batch->quantity_on_hand);
    }

    public function test_invoice_rounding_rounds_to_nearest_taka(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();

        ProductBatch::query()->where('product_id', $product->getKey())->delete();

        $batch = $this->makeBatch($product, [
            'batch_no' => 'ROUND-1',
            'expiry_date' => now()->addMonths(6)->toDateString(),
            'quantity_on_hand' => 10,
        ]);

        $tenant = \App\Domain\Tenant\Models\Tenant::query()->firstOrFail();
        $settings = $tenant->settings ?? [];
        $settings['invoice_rounding'] = 'nearest_1';
        $tenant->update(['settings' => $settings]);

        $this->actingAs($user)->post('/pos/sales', [
            'lines' => [[
                'product_batch_id' => $batch->getKey(),
                'quantity' => 3,
                'sell_unit' => 'strip',
                'unit_price' => 33.25,
            ]],
            'payments' => [['method' => 'cash', 'amount' => 100]],
        ])->assertRedirect();

        $sale = Sale::query()->latest('id')->firstOrFail();
        $this->assertSame(99.75, (float) $sale->total);
        $this->assertSame(100.0, (float) $sale->rounded_total);
        $this->assertSame(0.25, (float) $sale->round_adjustment);
        $this->assertSame(100.0, (float) $sale->paid);
        $this->assertSame(0.0, (float) $sale->change_returned);
        $this->assertSame(0.0, (float) $sale->due);
    }

    public function test_invoice_rounding_down_gives_change(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();

        ProductBatch::query()->where('product_id', $product->getKey())->delete();

        $batch = $this->makeBatch($product, [
            'batch_no' => 'ROUND-2',
            'expiry_date' => now()->addMonths(6)->toDateString(),
            'quantity_on_hand' => 10,
        ]);

        $tenant = \App\Domain\Tenant\Models\Tenant::query()->firstOrFail();
        $settings = $tenant->settings ?? [];
        $settings['invoice_rounding'] = 'nearest_1';
        $tenant->update(['settings' => $settings]);

        $this->actingAs($user)->post('/pos/sales', [
            'lines' => [[
                'product_batch_id' => $batch->getKey(),
                'quantity' => 3,
                'sell_unit' => 'strip',
                'unit_price' => 33.10,
            ]],
            'payments' => [['method' => 'cash', 'amount' => 100]],
        ])->assertRedirect();

        $sale = Sale::query()->latest('id')->firstOrFail();
        $this->assertSame(99.30, (float) $sale->total);
        $this->assertSame(99.0, (float) $sale->rounded_total);
        $this->assertSame(-0.30, (float) $sale->round_adjustment);
        $this->assertSame(99.0, (float) $sale->paid);
        $this->assertSame(1.0, (float) $sale->change_returned);
        $this->assertSame(0.0, (float) $sale->due);
    }

    public function test_sale_invoice_print_is_available_to_authorized_user(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $sale = Sale::query()->latest('id')->first();
        if (! $sale) {
            $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();
            $batch = ProductBatch::query()->where('product_id', $product->getKey())->firstOrFail();
            $this->actingAs($user)->post('/pos/sales', [
                'lines' => [[
                    'product_batch_id' => $batch->getKey(),
                    'quantity' => 1,
                    'sell_unit' => 'strip',
                    'unit_price' => 5,
                ]],
                'payments' => [['method' => 'cash', 'amount' => 5]],
            ]);
            $sale = Sale::query()->latest('id')->firstOrFail();
        }

        $this->actingAs($user)
            ->get('/sales/'.$sale->getKey().'/print')
            ->assertOk()
            ->assertSee($sale->invoice_no, false);
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
