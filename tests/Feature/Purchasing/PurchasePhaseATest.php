<?php

namespace Tests\Feature\Purchasing;

use App\Domain\Catalog\Models\Product;
use App\Domain\Purchasing\Models\Purchase;
use App\Domain\Purchasing\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchasePhaseATest extends TestCase
{
    use RefreshDatabase;

    private function recordSamplePurchase(): Purchase
    {
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();
        $supplier = Supplier::query()->firstOrFail();

        $this->actingAs($user)->post('/purchases', [
            'supplier_id' => $supplier->getKey(),
            'invoice_no' => 'PHASE-A-001',
            'purchased_at' => '2026-06-01',
            'paid' => 50,
            'payment_method' => 'cash',
            'discount' => 5,
            'tax' => 2,
            'lines' => [[
                'product_id' => $product->getKey(),
                'batch_no' => 'LOT-PHASE-A',
                'expiry_date' => '2028-06-01',
                'quantity' => 4,
                'sell_unit' => 'strip',
                'unit_cost' => 20,
            ]],
        ])->assertRedirect(route('tenant.purchases.index'));

        return Purchase::query()->where('invoice_no', 'PHASE-A-001')->firstOrFail();
    }

    public function test_purchase_show_displays_lines_and_stock_impact(): void
    {
        $this->seed();
        $purchase = $this->recordSamplePurchase();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get("/purchases/{$purchase->getKey()}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Purchases/Show')
                ->where('purchase.invoice_no', 'PHASE-A-001')
                ->has('purchase.lines', 1)
                ->where('purchase.lines.0.batch_no', 'LOT-PHASE-A')
                ->where('purchase.lines.0.quantity_base', '4.0000'));
    }

    public function test_purchase_index_filters_by_supplier_and_date(): void
    {
        $this->seed();
        $purchase = $this->recordSamplePurchase();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get('/purchases?'.http_build_query([
                'supplier_id' => $purchase->supplier_id,
                'date_from' => '2026-06-01',
                'date_to' => '2026-06-01',
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Purchases/Index')
                ->has('purchases.data', 1)
                ->where('purchases.data.0.invoice_no', 'PHASE-A-001'));

        $this->actingAs($user)
            ->get('/purchases?date_from=2026-07-01')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Purchases/Index')
                ->has('purchases.data', 0));
    }

    public function test_purchase_index_search_by_invoice_and_product_name(): void
    {
        $this->seed();
        $this->recordSamplePurchase();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get('/purchases?q=PHASE-A-001')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('purchases.data', 1)
                ->where('purchases.data.0.invoice_no', 'PHASE-A-001'));

        $this->actingAs($user)
            ->get('/purchases?q=Paracetamol')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('purchases.data', 1));

        $this->actingAs($user)
            ->get('/purchases?q=NONEXISTENT-XYZ')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('purchases.data', 0));
    }

    public function test_purchase_print_returns_html(): void
    {
        $this->seed();
        $purchase = $this->recordSamplePurchase();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get("/purchases/{$purchase->getKey()}/print")
            ->assertOk()
            ->assertSee('PHASE-A-001')
            ->assertSee('LOT-PHASE-A')
            ->assertSee('Paracetamol', false);
    }
}
