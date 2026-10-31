<?php

namespace Tests\Feature\Sales;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductBatch;
use App\Domain\Sales\Models\Sale;
use App\Models\User;
use Tests\TestCase;

class SaleAttributionTest extends TestCase
{
    public function test_pos_sale_stores_cashier_and_sales_list_shows_seller_and_items(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();

        ProductBatch::query()->where('product_id', $product->getKey())->delete();
        $batch = ProductBatch::query()->create([
            'tenant_id' => $product->tenant_id,
            'product_id' => $product->getKey(),
            'batch_no' => 'SOLD-BY-1',
            'expiry_date' => now()->addMonths(6)->toDateString(),
            'quantity_on_hand' => 10,
            'purchase_unit_cost' => 8,
            'sale_price' => 12,
        ]);

        $this->actingAs($user)->post('/pos/sales', [
            'lines' => [[
                'product_batch_id' => $batch->getKey(),
                'quantity' => 2,
                'sell_unit' => 'strip',
                'unit_price' => 12,
            ]],
            'payments' => [['method' => 'cash', 'amount' => 24]],
        ])->assertRedirect();

        $sale = Sale::query()->latest('id')->firstOrFail();
        $this->assertSame($user->getKey(), (int) $sale->sold_by_user_id);

        $this->actingAs($user)
            ->get(route('tenant.sales.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Sales/Index')
                ->has('sales.data', 1)
                ->where('sales.data.0.sold_by_user.name', $user->name)
                ->where('sales.data.0.lines.0.product.name', $product->name));
    }
}
