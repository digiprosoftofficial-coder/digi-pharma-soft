<?php

namespace Tests\Feature\Purchasing;

use App\Domain\Accounting\Models\LedgerEntry;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductBatch;
use App\Domain\Purchasing\Models\Purchase;
use App\Domain\Purchasing\Models\PurchaseReturn;
use App\Domain\Purchasing\Models\Supplier;
use App\Domain\Purchasing\Services\SupplierDueService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchasePhaseDTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_creates_inventory_and_payable_ledger_entries(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();
        $supplier = Supplier::query()->firstOrFail();

        $this->actingAs($user)->post('/purchases', [
            'supplier_id' => $supplier->getKey(),
            'invoice_no' => 'PHASE-D-VOUCHER',
            'purchased_at' => now()->toDateString(),
            'paid' => 0,
            'lines' => [[
                'product_id' => $product->getKey(),
                'batch_no' => 'LOT-D-V',
                'expiry_date' => '2028-06-01',
                'manufactured_at' => '2026-01-01',
                'quantity' => 10,
                'sell_unit' => 'strip',
                'unit_cost' => 12,
            ]],
        ])->assertRedirect();

        $purchase = Purchase::query()->where('invoice_no', 'PHASE-D-VOUCHER')->firstOrFail();

        $this->assertDatabaseHas('ledger_entries', [
            'reference_type' => Purchase::class,
            'reference_id' => $purchase->getKey(),
            'direction' => 'debit',
            'amount' => '120.0000',
        ]);
        $this->assertDatabaseHas('ledger_entries', [
            'reference_type' => Purchase::class,
            'reference_id' => $purchase->getKey(),
            'direction' => 'credit',
            'amount' => '120.0000',
        ]);
    }

    public function test_purchase_return_reduces_stock_and_supplier_balance(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();
        $supplier = Supplier::query()->firstOrFail();
        $dues = app(SupplierDueService::class);
        $balanceBefore = $dues->totalDue($supplier);

        $this->actingAs($user)->post('/purchases', [
            'supplier_id' => $supplier->getKey(),
            'invoice_no' => 'PHASE-D-RET-BASE',
            'purchased_at' => now()->toDateString(),
            'paid' => 0,
            'lines' => [[
                'product_id' => $product->getKey(),
                'batch_no' => 'LOT-D-RET',
                'expiry_date' => '2028-06-01',
                'manufactured_at' => '2026-01-01',
                'quantity' => 20,
                'sell_unit' => 'strip',
                'unit_cost' => 10,
            ]],
        ])->assertRedirect();

        $batch = ProductBatch::query()
            ->where('product_id', $product->getKey())
            ->where('batch_no', 'LOT-D-RET')
            ->firstOrFail();

        $this->actingAs($user)->post('/purchases/returns', [
            'supplier_id' => $supplier->getKey(),
            'lines' => [[
                'product_batch_id' => $batch->getKey(),
                'quantity' => 5,
                'unit_cost' => 10,
            ]],
        ])->assertRedirect(route('tenant.purchases.returns.index'));

        $batch->refresh();

        $this->assertEquals(15.0, (float) $batch->quantity_on_hand);
        $this->assertEquals($balanceBefore + 150, $dues->totalDue($supplier));
        $this->assertDatabaseHas('stock_movements', ['type' => 'purchase_return', 'quantity_delta' => '-5.0000']);
        $this->assertEquals(1, PurchaseReturn::query()->count());
    }

    public function test_purchase_can_be_voided_with_stock_and_ledger_reversal(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();
        $supplier = Supplier::query()->firstOrFail();

        $this->actingAs($user)->post('/purchases', [
            'supplier_id' => $supplier->getKey(),
            'invoice_no' => 'PHASE-D-VOID',
            'purchased_at' => now()->toDateString(),
            'paid' => 0,
            'lines' => [[
                'product_id' => $product->getKey(),
                'batch_no' => 'LOT-D-VOID',
                'expiry_date' => '2028-06-01',
                'manufactured_at' => '2026-01-01',
                'quantity' => 8,
                'sell_unit' => 'strip',
                'unit_cost' => 15,
            ]],
        ])->assertRedirect();

        $purchase = Purchase::query()->where('invoice_no', 'PHASE-D-VOID')->firstOrFail();
        $batch = ProductBatch::query()
            ->where('product_id', $product->getKey())
            ->where('batch_no', 'LOT-D-VOID')
            ->firstOrFail();
        $qtyBeforeVoid = (float) $batch->quantity_on_hand;

        $entriesBefore = LedgerEntry::query()->where('reference_type', Purchase::class)
            ->where('reference_id', $purchase->getKey())
            ->count();

        $this->actingAs($user)->post("/purchases/{$purchase->getKey()}/void")
            ->assertRedirect(route('tenant.purchases.show', $purchase));

        $purchase->refresh();
        $batch->refresh();

        $this->assertSame('voided', $purchase->status);
        $this->assertEquals($qtyBeforeVoid - 8, (float) $batch->quantity_on_hand);
        $this->assertGreaterThan($entriesBefore, LedgerEntry::query()->where('reference_type', Purchase::class)
            ->where('reference_id', $purchase->getKey())
            ->count());
        $this->assertDatabaseHas('stock_movements', [
            'type' => 'purchase_void',
            'product_batch_id' => $batch->getKey(),
            'quantity_delta' => '-8.0000',
        ]);
    }

    public function test_purchase_list_csv_export_respects_filters(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $supplier = Supplier::query()->firstOrFail();

        $this->actingAs($user)->get('/purchases/export?date_from='.now()->subYear()->toDateString())
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $response = $this->actingAs($user)->get('/purchases/export?q=PHASE-D-NOMATCH&date_from='.now()->toDateString());
        $response->assertOk();
        $body = $response->streamedContent();
        $this->assertStringContainsString('invoice_no', $body);
        $this->assertStringNotContainsString('PHASE-D-NOMATCH', $body);
    }

    public function test_inventory_page_includes_expiry_alert_batches(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();

        ProductBatch::query()->create([
            'tenant_id' => $product->tenant_id,
            'product_id' => $product->getKey(),
            'batch_no' => 'EXP-OLD',
            'expiry_date' => now()->subDays(5)->toDateString(),
            'quantity_on_hand' => 12,
            'purchase_unit_cost' => 5,
        ]);

        ProductBatch::query()->create([
            'tenant_id' => $product->tenant_id,
            'product_id' => $product->getKey(),
            'batch_no' => 'EXP-SOON',
            'expiry_date' => now()->addDays(10)->toDateString(),
            'quantity_on_hand' => 6,
            'purchase_unit_cost' => 5,
        ]);

        $this->actingAs($user)->get('/inventory')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Inventory/Index')
                ->has('expiredBatches', 1)
                ->has('expiringWithin30', 1)
            );
    }
}
