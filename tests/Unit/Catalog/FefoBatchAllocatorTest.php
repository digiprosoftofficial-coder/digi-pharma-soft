<?php

namespace Tests\Unit\Catalog;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductBatch;
use App\Support\Catalog\FefoBatchAllocator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class FefoBatchAllocatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_allocates_earliest_expiry_first(): void
    {
        $this->seed();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();

        $later = $this->makeBatch($product, [
            'batch_no' => 'LOT-LATE',
            'expiry_date' => now()->addMonths(6)->toDateString(),
            'quantity_on_hand' => 30,
        ]);
        $sooner = $this->makeBatch($product, [
            'batch_no' => 'LOT-SOON',
            'expiry_date' => now()->addMonth()->toDateString(),
            'quantity_on_hand' => 20,
        ]);

        $allocator = new FefoBatchAllocator;

        $allocations = $this->runInTransaction(fn () => $allocator->allocateForProduct(
            (int) $product->getKey(),
            25.0,
        ));

        $this->assertCount(2, $allocations);
        $this->assertSame((int) $sooner->getKey(), $allocations[0]['product_batch_id']);
        $this->assertSame(20.0, $allocations[0]['quantity_base']);
        $this->assertSame((int) $later->getKey(), $allocations[1]['product_batch_id']);
        $this->assertSame(5.0, $allocations[1]['quantity_base']);
    }

    public function test_preferred_batch_is_consumed_first(): void
    {
        $this->seed();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();

        $sooner = $this->makeBatch($product, [
            'batch_no' => 'LOT-SOON-2',
            'expiry_date' => now()->addWeek()->toDateString(),
            'quantity_on_hand' => 50,
        ]);
        $later = $this->makeBatch($product, [
            'batch_no' => 'LOT-LATE-2',
            'expiry_date' => now()->addYear()->toDateString(),
            'quantity_on_hand' => 50,
        ]);

        $allocator = new FefoBatchAllocator;

        $allocations = $this->runInTransaction(fn () => $allocator->allocateForProduct(
            (int) $product->getKey(),
            10.0,
            (int) $later->getKey(),
        ));

        $this->assertCount(1, $allocations);
        $this->assertSame((int) $later->getKey(), $allocations[0]['product_batch_id']);
        $this->assertSame(10.0, $allocations[0]['quantity_base']);

        $sooner->refresh();
        $this->assertSame(50.0, (float) $sooner->quantity_on_hand);
    }

    public function test_throws_when_insufficient_stock(): void
    {
        $this->seed();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();
        $batch = ProductBatch::query()->where('product_id', $product->getKey())->firstOrFail();

        $allocator = new FefoBatchAllocator;

        $this->expectException(RuntimeException::class);

        $this->runInTransaction(fn () => $allocator->allocateForProduct(
            (int) $product->getKey(),
            (float) $batch->quantity_on_hand + 1000,
        ));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeBatch(Product $product, array $attributes): ProductBatch
    {
        return ProductBatch::query()->create(array_merge([
            'tenant_id' => $product->tenant_id,
            'product_id' => $product->getKey(),
            'purchase_unit_cost' => 1,
        ], $attributes));
    }

    /**
     * @template T
     * @param  callable(): T  $callback
     * @return T
     */
    private function runInTransaction(callable $callback): mixed
    {
        return \Illuminate\Support\Facades\DB::transaction($callback);
    }
}
