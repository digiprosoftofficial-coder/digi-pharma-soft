<?php

namespace Tests\Feature\Purchasing;

use App\Domain\Catalog\Models\Product;
use App\Domain\Purchasing\Models\Purchase;
use App\Domain\Purchasing\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchasePhaseCTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_can_create_new_supplier_on_the_fly(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();

        $this->actingAs($user)->post('/purchases', [
            'invoice_no' => 'PHASE-C-SUP-1',
            'purchased_at' => now()->toDateString(),
            'paid' => 0,
            'new_supplier' => [
                'name' => 'Fresh Pharma Ltd',
                'phone' => '01700001111',
            ],
            'notes' => 'Urgent delivery',
            'lines' => [[
                'product_id' => $product->getKey(),
                'batch_no' => 'LOT-C-1',
                'expiry_date' => '2029-01-01',
                'manufactured_at' => '2026-01-15',
                'quantity' => 5,
                'sell_unit' => 'strip',
                'unit_cost' => 19,
                'sale_price' => 25,
            ]],
        ])->assertRedirect(route('tenant.purchases.index'));

        $supplier = Supplier::query()->where('name', 'Fresh Pharma Ltd')->first();
        $this->assertNotNull($supplier);
        $this->assertDatabaseHas('purchases', [
            'invoice_no' => 'PHASE-C-SUP-1',
            'supplier_id' => $supplier->getKey(),
            'notes' => 'Urgent delivery',
        ]);
        $this->assertDatabaseHas('purchase_lines', [
            'batch_no' => 'LOT-C-1',
            'manufactured_at' => '2026-01-15',
            'sale_price' => '25.0000',
        ]);
    }

    public function test_purchase_updates_catalog_sale_and_purchase_prices(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();
        $supplier = Supplier::query()->firstOrFail();

        $this->actingAs($user)->post('/purchases', [
            'supplier_id' => $supplier->getKey(),
            'invoice_no' => 'PHASE-C-PRICE',
            'purchased_at' => now()->toDateString(),
            'paid' => 0,
            'lines' => [[
                'product_id' => $product->getKey(),
                'batch_no' => 'LOT-C-PRICE',
                'expiry_date' => '2028-06-01',
                'manufactured_at' => '2026-01-01',
                'quantity' => 2,
                'sell_unit' => 'strip',
                'unit_cost' => 21.5,
                'sale_price' => 28,
            ]],
        ])->assertRedirect();

        $product->refresh()->load('units');
        $stripUnit = $product->units->firstWhere('sell_unit', 'strip');
        $this->assertNotNull($stripUnit);
        $this->assertSame('21.5000', (string) $stripUnit->purchase_price);
        $this->assertSame('28.0000', (string) $stripUnit->sale_price);
    }

    public function test_product_search_includes_last_purchase_after_prior_buy(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();
        $supplier = Supplier::query()->firstOrFail();

        $this->actingAs($user)->post('/purchases', [
            'supplier_id' => $supplier->getKey(),
            'invoice_no' => 'PHASE-C-LAST',
            'purchased_at' => '2026-05-01',
            'paid' => 0,
            'lines' => [[
                'product_id' => $product->getKey(),
                'batch_no' => 'LOT-C-LAST',
                'expiry_date' => '2028-06-01',
                'manufactured_at' => '2026-01-01',
                'quantity' => 1,
                'sell_unit' => 'strip',
                'unit_cost' => 17.25,
            ]],
        ]);

        $this->actingAs($user)
            ->getJson('/catalog/product-search?q=PAR')
            ->assertOk()
            ->assertJsonPath('data.0.last_purchase.unit_cost', '17.2500')
            ->assertJsonPath('data.0.last_purchase.sell_unit', 'strip');
    }

    public function test_supplier_search_returns_matches(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($user)
            ->getJson('/purchases/supplier-search?q=Main')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name', 'phone']]]);
    }

    public function test_last_purchase_endpoint_filters_by_sell_unit(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();
        $supplier = Supplier::query()->firstOrFail();

        $this->actingAs($user)->post('/purchases', [
            'supplier_id' => $supplier->getKey(),
            'invoice_no' => 'PHASE-C-UNIT',
            'purchased_at' => now()->toDateString(),
            'paid' => 0,
            'lines' => [[
                'product_id' => $product->getKey(),
                'batch_no' => 'LOT-C-UNIT',
                'expiry_date' => '2028-06-01',
                'manufactured_at' => '2026-01-01',
                'quantity' => 1,
                'sell_unit' => 'strip',
                'unit_cost' => 16,
            ]],
        ]);

        $this->actingAs($user)
            ->getJson("/catalog/products/{$product->getKey()}/last-purchase?sell_unit=strip")
            ->assertOk()
            ->assertJsonPath('data.unit_cost', '16.0000');
    }
}
