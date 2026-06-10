<?php

namespace Tests\Feature\Purchasing;

use App\Domain\Catalog\Models\Product;
use App\Domain\Purchasing\Models\Purchase;
use App\Domain\Purchasing\Models\PurchasePayment;
use App\Domain\Purchasing\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchasePhaseBTest extends TestCase
{
    use RefreshDatabase;

    private function recordDuePurchase(float $paid = 0): Purchase
    {
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();
        $supplier = Supplier::query()->firstOrFail();

        $payload = [
            'supplier_id' => $supplier->getKey(),
            'invoice_no' => 'PHASE-B-'.uniqid(),
            'purchased_at' => now()->toDateString(),
            'paid' => $paid,
            'lines' => [[
                'product_id' => $product->getKey(),
                'batch_no' => 'LOT-B-'.uniqid(),
                'expiry_date' => '2028-06-01',
                'quantity' => 5,
                'sell_unit' => 'strip',
                'unit_cost' => 20,
            ]],
        ];

        if ($paid > 0) {
            $payload['payment_method'] = 'cash';
        }

        $this->actingAs($user)->post('/purchases', $payload)->assertRedirect(route('tenant.purchases.index'));

        return Purchase::query()->where('invoice_no', $payload['invoice_no'])->firstOrFail();
    }

    public function test_purchase_with_initial_payment_creates_payment_record(): void
    {
        $this->seed();
        $purchase = $this->recordDuePurchase(40);

        $this->assertDatabaseHas('purchase_payments', [
            'purchase_id' => $purchase->getKey(),
            'method' => 'cash',
            'amount' => '40.0000',
        ]);
    }

    public function test_partial_payment_reduces_due_and_updates_supplier_balance(): void
    {
        $this->seed();
        $purchase = $this->recordDuePurchase(0);
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $supplier = Supplier::query()->findOrFail($purchase->supplier_id);

        $this->assertSame('100.0000', (string) $purchase->due);
        $this->assertSame('100.0000', (string) $supplier->balance_due);

        $this->actingAs($user)
            ->post("/purchases/{$purchase->getKey()}/payments", [
                'method' => 'bkash',
                'amount' => 30,
            ])
            ->assertRedirect(route('tenant.purchases.show', $purchase));

        $purchase->refresh();
        $supplier->refresh();

        $this->assertSame('30.0000', (string) $purchase->paid);
        $this->assertSame('70.0000', (string) $purchase->due);
        $this->assertSame('70.0000', (string) $supplier->balance_due);
        $this->assertDatabaseHas('purchase_payments', [
            'purchase_id' => $purchase->getKey(),
            'method' => 'bkash',
            'amount' => '30.0000',
        ]);
    }

    public function test_supplier_bill_show_lists_open_invoices_and_payment_history(): void
    {
        $this->seed();
        $purchase = $this->recordDuePurchase(25);
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get("/purchases/supplier-bills/{$purchase->supplier_id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Purchases/SupplierBillShow')
                ->has('openPurchases', 1)
                ->where('openPurchases.0.invoice_no', $purchase->invoice_no)
                ->has('paymentHistory', 1));

        $this->actingAs($user)
            ->post("/purchases/{$purchase->getKey()}/payments", [
                'method' => 'bank',
                'amount' => 75,
                'redirect' => 'supplier_bill',
            ])
            ->assertRedirect(route('tenant.purchases.supplier-bills.show', $purchase->supplier_id));

        $purchase->refresh();
        $this->assertSame('0.0000', (string) $purchase->due);
        $this->assertSame(2, PurchasePayment::query()->where('purchase_id', $purchase->getKey())->count());

        $this->actingAs($user)
            ->get("/purchases/supplier-bills/{$purchase->supplier_id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('openPurchases', 0)->has('paymentHistory', 2));
    }

    public function test_cannot_pay_more_than_due(): void
    {
        $this->seed();
        $purchase = $this->recordDuePurchase(0);
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($user)
            ->from("/purchases/{$purchase->getKey()}")
            ->post("/purchases/{$purchase->getKey()}/payments", [
                'method' => 'cash',
                'amount' => 150,
            ])
            ->assertSessionHasErrors('amount');
    }
}
