<?php

namespace Tests\Feature\Catalog;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductBatch;
use App\Domain\Sales\Models\Sale;
use App\Domain\Tenant\Models\Branch;
use App\Models\User;
use App\Support\Catalog\ProductUnitResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductUnitSynthesisTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolver_synthesizes_piece_factor_from_pieces_per_strip(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $tenantId = $user->tenant_id;

        $product = Product::query()->create([
            'tenant_id' => $tenantId,
            'name' => 'Synth Piece Med',
            'sku' => 'SYNTH-PIECE-1',
            'product_type' => 'tablet',
            'base_unit' => 'strip',
            'unit' => 'strip',
            'pieces_per_strip' => 10,
            'strips_per_box' => 10,
            'purchase_price' => 20,
            'sale_price' => 35,
            'is_active' => true,
        ]);

        ProductUnitResolver::syncProductUnits($product, [
            ['sell_unit' => 'strip', 'conversion_factor' => 1, 'purchase_price' => 20, 'sale_price' => 35, 'is_default' => true],
        ]);
        $product->refresh();

        $this->assertSame(0.1, ProductUnitResolver::synthesizedConversionFactor($product, 'piece'));
        $this->assertSame(10.0, ProductUnitResolver::synthesizedConversionFactor($product, 'box'));
        $this->assertEqualsWithDelta(0.1, ProductUnitResolver::resolveConversionFactor($product, 'piece'), 0.0001);
    }

    public function test_pos_can_sell_by_piece_without_piece_unit_row(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $tenantId = $user->tenant_id;
        $branchId = Branch::query()->where('tenant_id', $tenantId)->where('is_default', true)->value('id');

        $product = Product::query()->create([
            'tenant_id' => $tenantId,
            'name' => 'Piece Sale Med',
            'sku' => 'PIECE-SALE-1',
            'product_type' => 'tablet',
            'base_unit' => 'strip',
            'unit' => 'strip',
            'pieces_per_strip' => 10,
            'purchase_price' => 20,
            'sale_price' => 35,
            'is_active' => true,
        ]);
        ProductUnitResolver::syncProductUnits($product, [
            ['sell_unit' => 'strip', 'conversion_factor' => 1, 'purchase_price' => 20, 'sale_price' => 35, 'is_default' => true],
        ]);

        $batch = ProductBatch::query()->withoutGlobalScopes()->create([
            'tenant_id' => $tenantId,
            'branch_id' => $branchId,
            'product_id' => $product->getKey(),
            'batch_no' => 'LOT-PIECE-1',
            'expiry_date' => now()->addYear()->toDateString(),
            'quantity_on_hand' => 5,
            'purchase_unit_cost' => 20,
            'sale_price' => 35,
        ]);

        $this->actingAs($user)->post('/pos/sales', [
            'lines' => [[
                'product_batch_id' => $batch->getKey(),
                'quantity' => 4,
                'sell_unit' => 'piece',
                'unit_price' => 3.5,
            ]],
            'payments' => [['method' => 'cash', 'amount' => 14]],
            'discount_percent' => 0,
            'tax' => 0,
        ])->assertRedirect();

        $sale = Sale::query()->latest('id')->firstOrFail();
        $line = $sale->lines()->firstOrFail();

        $this->assertSame('piece', $line->sell_unit);
        $this->assertEqualsWithDelta(0.1, (float) $line->conversion_factor, 0.0001);
        $this->assertEqualsWithDelta(0.4, (float) $line->quantity_base, 0.0001);
        $this->assertEqualsWithDelta(4.6, (float) $batch->fresh()->quantity_on_hand, 0.0001);
    }
}
