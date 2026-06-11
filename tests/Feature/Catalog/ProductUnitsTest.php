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

    public function test_product_index_shows_stock_and_purchased_quantities(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();
        $supplier = \App\Domain\Purchasing\Models\Supplier::query()->firstOrFail();

        $this->actingAs($user)->post('/purchases', [
            'supplier_id' => $supplier->getKey(),
            'invoice_no' => 'INV-TEST-001',
            'purchased_at' => now()->toDateString(),
            'paid' => 0,
            'lines' => [[
                'product_id' => $product->getKey(),
                'batch_no' => 'BATCH-PUR-1',
                'quantity' => 30,
                'sell_unit' => 'strip',
                'unit_cost' => 20,
            ]],
        ])->assertRedirect();

        $response = $this->actingAs($user)->get('/products');
        $response->assertOk()->assertInertia(fn ($page) => $page->component('Catalog/Products/Index'));

        $row = collect($response->inertiaProps('products.data'))->firstWhere('sku', 'PAR-500');
        $this->assertNotNull($row);
        $this->assertSame(530.0, (float) $row['stock_on_hand']);
        $this->assertSame(30.0, (float) $row['purchased_quantity']);
    }

    public function test_product_show_page_displays_stock_by_unit(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();

        $this->actingAs($user)
            ->get("/products/{$product->getKey()}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Catalog/Products/Show')
                ->where('product.sku', 'PAR-500')
                ->where('stockBase', '500.0000')
                ->has('stockByUnit', 2)
                ->where('stockByUnit.0.sell_unit', 'strip')
                ->where('stockByUnit.0.quantity_on_hand', '500.0000')
                ->where('stockByUnit.1.sell_unit', 'box')
                ->where('stockByUnit.1.quantity_on_hand', '50.0000'));
    }

    public function test_product_edit_page_includes_resolved_product_data(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();

        $this->actingAs($user)
            ->get("/products/{$product->getKey()}/edit")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Catalog/Products/Form')
                ->where('product.name', 'Paracetamol 500mg')
                ->where('product.sku', 'PAR-500')
                ->has('product.units', 2));
    }

    public function test_product_update_adjusts_stock_on_single_batch(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();
        $batch = ProductBatch::query()->where('product_id', $product->getKey())->firstOrFail();
        $before = (float) $batch->quantity_on_hand;

        $this->actingAs($user)->put("/products/{$product->getKey()}", [
            'stock_adjustment' => 25,
        ])->assertRedirect(route('tenant.products.show', $product));

        $batch->refresh();
        $this->assertSame($before + 25.0, (float) $batch->quantity_on_hand);

        $this->assertDatabaseHas('stock_movements', [
            'product_batch_id' => $batch->getKey(),
            'type' => 'adjustment',
            'quantity_delta' => '25.0000',
        ]);
    }

    public function test_product_create_with_opening_quantity_creates_batch(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($user)->post('/products', [
            'name' => 'Vitamin C',
            'sku' => 'VIT-C-100',
            'product_type' => 'tablet',
            'base_unit' => 'strip',
            'units' => [
                ['sell_unit' => 'strip', 'conversion_factor' => 1, 'purchase_price' => 10, 'sale_price' => 15, 'is_default' => true],
            ],
            'opening_quantity' => 80,
        ])->assertRedirect(route('tenant.products.index'));

        $product = Product::query()->where('sku', 'VIT-C-100')->firstOrFail();
        $batch = ProductBatch::query()->where('product_id', $product->getKey())->firstOrFail();
        $this->assertSame(80.0, (float) $batch->quantity_on_hand);
        $this->assertStringStartsWith('OPEN-', $batch->batch_no);
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

    public function test_product_can_be_created_with_carton_sell_unit(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($user)->post('/products', [
            'name' => 'Bulk Paracetamol',
            'sku' => 'PAR-BULK',
            'product_type' => 'tablet',
            'base_unit' => 'strip',
            'units' => [
                ['sell_unit' => 'strip', 'conversion_factor' => 1, 'purchase_price' => 50, 'sale_price' => 70, 'is_default' => true],
                ['sell_unit' => 'carton', 'conversion_factor' => 120, 'purchase_price' => 5400, 'sale_price' => 7800, 'is_default' => false],
            ],
            'min_stock' => 5,
            'is_active' => true,
        ])->assertRedirect(route('tenant.products.index'));

        $product = Product::query()->where('sku', 'PAR-BULK')->firstOrFail();
        $carton = $product->units()->where('sell_unit', 'carton')->first();
        $this->assertNotNull($carton);
        $this->assertSame(120.0, (float) $carton->conversion_factor);
    }

    public function test_pos_sale_deducts_stock_in_base_units_when_selling_cartons(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();
        $product->units()->create([
            'sell_unit' => 'carton',
            'conversion_factor' => 100,
            'purchase_price' => 4500,
            'sale_price' => 6000,
            'is_default' => false,
            'sort_order' => 2,
        ]);
        $batch = ProductBatch::query()->where('product_id', $product->getKey())->firstOrFail();
        $before = (float) $batch->quantity_on_hand;

        $this->actingAs($user)->post('/pos/sales', [
            'lines' => [[
                'product_batch_id' => $batch->getKey(),
                'quantity' => 1,
                'sell_unit' => 'carton',
                'unit_price' => 6000,
            ]],
            'payments' => [['method' => 'cash', 'amount' => 6000]],
        ])->assertRedirect();

        $batch->refresh();
        $this->assertSame($before - 100.0, (float) $batch->quantity_on_hand);

        $line = SaleLine::query()->latest('id')->first();
        $this->assertSame('carton', $line->sell_unit);
        $this->assertSame(100.0, (float) $line->quantity_base);
    }
}
