<?php

namespace Tests\Feature\Sales;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductBatch;
use App\Domain\Sales\Models\Customer;
use App\Domain\Sales\Models\Sale;
use App\Domain\Sales\Models\SalePayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerBillPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_bills_lists_customers_with_open_due(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $customer = $this->createCustomerWithDue(80);

        $this->actingAs($user)
            ->get('/sales/customer-bills')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Sales/CustomerBills')
                ->has('customers.data', 1)
                ->where('customers.data.0.id', $customer->getKey())
            );
    }

    public function test_partial_payment_reduces_sale_due_and_customer_balance(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $customer = $this->createCustomerWithDue(100);
        $sale = Sale::query()->where('customer_id', $customer->getKey())->firstOrFail();

        $this->actingAs($user)
            ->post("/sales/{$sale->getKey()}/payments", [
                'method' => 'cash',
                'amount' => 40,
                'redirect' => 'customer_bill',
            ])
            ->assertRedirect(route('tenant.sales.customer-bills.show', $customer));

        $sale->refresh();
        $customer->refresh();

        $this->assertSame('60.0000', (string) $sale->due);
        $this->assertSame('60.0000', (string) $sale->paid);
        $this->assertSame(60.0, (float) $customer->balance_due);
        $this->assertEquals(2, SalePayment::query()->where('sale_id', $sale->getKey())->count());
    }

    public function test_full_payment_clears_invoice_from_open_list(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $customer = $this->createCustomerWithDue(50);
        $sale = Sale::query()->where('customer_id', $customer->getKey())->firstOrFail();

        $this->actingAs($user)
            ->post("/sales/{$sale->getKey()}/payments", [
                'method' => 'bkash',
                'amount' => 50,
                'redirect' => 'customer_bill',
            ])
            ->assertRedirect(route('tenant.sales.customer-bills.show', $customer));

        $sale->refresh();
        $customer->refresh();

        $this->assertSame('0.0000', (string) $sale->due);
        $this->assertSame(0.0, (float) $customer->balance_due);

        $this->actingAs($user)
            ->get("/sales/customer-bills/{$customer->getKey()}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('openSales', 0));
    }

    public function test_cannot_pay_more_than_due(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $customer = $this->createCustomerWithDue(30);
        $sale = Sale::query()->where('customer_id', $customer->getKey())->firstOrFail();

        $this->actingAs($user)
            ->from("/sales/customer-bills/{$customer->getKey()}")
            ->post("/sales/{$sale->getKey()}/payments", [
                'method' => 'cash',
                'amount' => 50,
            ])
            ->assertSessionHasErrors('amount');
    }

    private function createCustomerWithDue(float $dueAmount): Customer
    {
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();

        ProductBatch::query()->where('product_id', $product->getKey())->delete();

        $batch = ProductBatch::query()->withoutGlobalScopes()->create([
            'tenant_id' => $product->tenant_id,
            'product_id' => $product->getKey(),
            'batch_no' => 'CUST-DUE-'.uniqid(),
            'expiry_date' => now()->addMonths(6)->toDateString(),
            'quantity_on_hand' => 20,
            'purchase_unit_cost' => 10,
        ]);

        $customer = Customer::query()->create([
            'tenant_id' => $product->tenant_id,
            'name' => 'Due Customer '.uniqid(),
            'balance_due' => 0,
        ]);

        $total = $dueAmount + 20;

        $this->actingAs($user)->post('/pos/sales', [
            'customer_id' => $customer->getKey(),
            'lines' => [[
                'product_batch_id' => $batch->getKey(),
                'quantity' => 1,
                'sell_unit' => 'strip',
                'unit_price' => $total,
            ]],
            'payments' => [['method' => 'cash', 'amount' => 20]],
        ])->assertRedirect();

        $customer->refresh();

        return $customer;
    }
}
