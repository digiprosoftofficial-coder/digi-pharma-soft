<?php

namespace Tests\Feature\Purchasing;

use App\Domain\Catalog\Models\Product;
use App\Domain\Purchasing\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_create_page_loads(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get('/purchases/create')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Purchases/Create')
                ->has('paymentMethods', 6));
    }

    public function test_product_search_returns_products_for_purchase(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($user)
            ->getJson('/catalog/product-search?q=PAR')
            ->assertOk()
            ->assertJsonPath('data.0.sku', 'PAR-500')
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'name',
                        'sku',
                        'units',
                        'batches',
                    ],
                ],
            ]);
    }

    public function test_purchase_can_be_recorded_via_product_id_from_catalog(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();
        $supplier = Supplier::query()->firstOrFail();

        $this->actingAs($user)->post('/purchases', [
            'supplier_id' => $supplier->getKey(),
            'invoice_no' => 'INV-UI-002',
            'purchased_at' => now()->toDateString(),
            'paid' => 0,
            'lines' => [[
                'product_id' => $product->getKey(),
                'batch_no' => 'LOT-NEW-99',
                'expiry_date' => '2028-01-01',
                'quantity' => 10,
                'sell_unit' => 'strip',
                'unit_cost' => 18,
            ]],
        ])->assertRedirect(route('tenant.purchases.index'));

        $this->assertDatabaseHas('purchase_lines', [
            'product_id' => $product->getKey(),
            'batch_no' => 'LOT-NEW-99',
            'quantity_base' => '10.0000',
        ]);
    }
}
