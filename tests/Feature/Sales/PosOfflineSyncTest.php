<?php

namespace Tests\Feature\Sales;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductBatch;
use App\Domain\Sales\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosOfflineSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_offline_catalog_returns_sellable_products(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($user)
            ->getJson('/pos/offline-catalog')
            ->assertOk()
            ->assertJsonStructure(['data', 'cached_at']);
    }

    public function test_offline_sale_sync_is_idempotent_by_client_id(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();
        $batch = ProductBatch::query()->where('product_id', $product->getKey())->firstOrFail();
        $batch->update(['quantity_on_hand' => 20]);

        $payload = [
            'offline_client_id' => '11111111-1111-4111-8111-111111111111',
            'lines' => [[
                'product_batch_id' => $batch->getKey(),
                'quantity' => 2,
                'sell_unit' => 'strip',
                'unit_price' => 10,
            ]],
            'payments' => [['method' => 'cash', 'amount' => 20]],
        ];

        $first = $this->actingAs($user)->postJson('/pos/sales/sync', $payload);
        $first->assertOk()->assertJson(['ok' => true, 'duplicate' => false]);

        $second = $this->actingAs($user)->postJson('/pos/sales/sync', $payload);
        $second->assertOk()->assertJson([
            'ok' => true,
            'duplicate' => true,
            'sale_id' => $first->json('sale_id'),
        ]);

        $this->assertSame(1, Sale::query()->where('offline_client_id', $payload['offline_client_id'])->count());
        $batch->refresh();
        $this->assertSame(18.0, (float) $batch->quantity_on_hand);
    }
}
