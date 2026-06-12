<?php

namespace Tests\Feature\Sales;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductBatch;
use App\Domain\Sales\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_delete_customer_with_sales(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();

        ProductBatch::query()->where('product_id', $product->getKey())->delete();

        $batch = $this->makeBatch($product, [
            'batch_no' => 'CUST-DEL-BLOCK',
            'expiry_date' => now()->addMonths(6)->toDateString(),
            'quantity_on_hand' => 10,
        ]);

        $customer = Customer::query()->create([
            'tenant_id' => $product->tenant_id,
            'name' => 'Buyer With Sale',
        ]);

        $this->actingAs($user)->post('/pos/sales', [
            'customer_id' => $customer->getKey(),
            'lines' => [[
                'product_batch_id' => $batch->getKey(),
                'quantity' => 1,
                'sell_unit' => 'strip',
                'unit_price' => 50,
            ]],
            'payments' => [['method' => 'cash', 'amount' => 50]],
        ])->assertRedirect();

        $this->actingAs($user)
            ->delete("/customers/{$customer->getKey()}")
            ->assertSessionHasErrors('customer');

        $this->assertDatabaseHas('customers', ['id' => $customer->getKey()]);
    }

    public function test_cannot_delete_customer_with_open_balance(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();

        ProductBatch::query()->where('product_id', $product->getKey())->delete();

        $batch = $this->makeBatch($product, [
            'batch_no' => 'CUST-DUE-BLOCK',
            'expiry_date' => now()->addMonths(6)->toDateString(),
            'quantity_on_hand' => 10,
        ]);

        $customer = Customer::query()->create([
            'tenant_id' => $product->tenant_id,
            'name' => 'Credit Buyer',
            'balance_due' => 0,
        ]);

        $this->actingAs($user)->post('/pos/sales', [
            'customer_id' => $customer->getKey(),
            'lines' => [[
                'product_batch_id' => $batch->getKey(),
                'quantity' => 1,
                'sell_unit' => 'strip',
                'unit_price' => 100,
            ]],
            'payments' => [['method' => 'cash', 'amount' => 40]],
        ])->assertRedirect();

        $customer->refresh();
        $this->assertGreaterThan(0, (float) $customer->balance_due);

        $this->actingAs($user)
            ->delete("/customers/{$customer->getKey()}")
            ->assertSessionHasErrors('customer');

        $this->assertDatabaseHas('customers', ['id' => $customer->getKey()]);
    }

    public function test_can_delete_customer_without_sales(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $customer = Customer::query()->create([
            'tenant_id' => $user->tenant_id,
            'name' => 'Unused Customer',
            'phone' => '01700000000',
        ]);

        $this->actingAs($user)
            ->delete("/customers/{$customer->getKey()}")
            ->assertRedirect(route('tenant.customers.index'));

        $this->assertDatabaseMissing('customers', ['id' => $customer->getKey()]);
    }

    public function test_can_create_and_update_customer_with_address(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($user)
            ->post('/customers', [
                'name' => 'Address Customer',
                'phone' => '01811112222',
                'email' => 'addr@example.com',
                'address' => '12 Main Road, Dhaka',
            ])
            ->assertRedirect(route('tenant.customers.index'));

        $customer = Customer::query()->where('name', 'Address Customer')->firstOrFail();
        $this->assertSame('12 Main Road, Dhaka', $customer->address);

        $this->actingAs($user)
            ->put("/customers/{$customer->getKey()}", [
                'name' => 'Address Customer',
                'phone' => '01811112222',
                'email' => 'addr@example.com',
                'address' => '45 New Street, Chittagong',
            ])
            ->assertRedirect(route('tenant.customers.index'));

        $customer->refresh();
        $this->assertSame('45 New Street, Chittagong', $customer->address);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeBatch(Product $product, array $overrides = []): ProductBatch
    {
        return ProductBatch::query()->withoutGlobalScopes()->create(array_merge([
            'tenant_id' => $product->tenant_id,
            'product_id' => $product->getKey(),
            'batch_no' => 'B-'.uniqid(),
            'expiry_date' => now()->addYear()->toDateString(),
            'quantity_on_hand' => 100,
            'purchase_unit_cost' => 10,
        ], $overrides));
    }
}
