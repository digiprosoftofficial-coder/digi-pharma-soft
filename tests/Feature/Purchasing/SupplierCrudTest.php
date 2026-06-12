<?php

namespace Tests\Feature\Purchasing;

use App\Domain\Catalog\Models\Product;
use App\Domain\Purchasing\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_delete_supplier_with_purchases(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $supplier = Supplier::query()->firstOrFail();

        $this->actingAs($user)->post('/purchases', [
            'supplier_id' => $supplier->getKey(),
            'invoice_no' => 'SUP-DEL-BLOCK',
            'purchased_at' => now()->toDateString(),
            'paid' => 0,
            'lines' => [[
                'product_id' => Product::query()->where('sku', 'PAR-500')->value('id'),
                'batch_no' => 'DEL-BLOCK',
                'quantity' => 1,
                'sell_unit' => 'strip',
                'unit_cost' => 10,
            ]],
        ])->assertRedirect();

        $this->actingAs($user)
            ->delete("/suppliers/{$supplier->getKey()}")
            ->assertSessionHasErrors('supplier');

        $this->assertDatabaseHas('suppliers', ['id' => $supplier->getKey()]);
    }

    public function test_can_delete_supplier_without_purchases(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $supplier = Supplier::query()->create([
            'tenant_id' => $user->tenant_id,
            'name' => 'Unused Supplier',
            'phone' => null,
            'email' => null,
        ]);

        $this->actingAs($user)
            ->delete("/suppliers/{$supplier->getKey()}")
            ->assertRedirect(route('tenant.suppliers.index'));

        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->getKey()]);
    }
}
