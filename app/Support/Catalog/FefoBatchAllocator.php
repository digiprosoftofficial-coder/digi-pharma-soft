<?php

namespace App\Support\Catalog;

use App\Domain\Catalog\Models\ProductBatch;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

final class FefoBatchAllocator
{
    /**
     * Allocate quantity (base units) across batches for a product, earliest expiry first.
     * When $preferredBatchId is set, that batch is consumed first, then remaining FEFO.
     *
     * @return array<int, array{product_batch_id: int, quantity_base: float}>
     */
    public function allocateForProduct(
        int $productId,
        float $quantityBase,
        ?int $preferredBatchId = null,
    ): array {
        if ($quantityBase <= 0) {
            return [];
        }

        $batches = $this->orderedBatchesWithStock($productId);

        if ($preferredBatchId !== null) {
            $batches = $this->prioritizeBatch($batches, $preferredBatchId);
        }

        $remaining = $quantityBase;
        $allocations = [];

        foreach ($batches as $batch) {
            if ($remaining <= 0.00005) {
                break;
            }

            $available = (float) $batch->quantity_on_hand;
            if ($available <= 0) {
                continue;
            }

            $take = min($remaining, $available);
            $allocations[] = [
                'product_batch_id' => (int) $batch->getKey(),
                'quantity_base' => $take,
            ];
            $remaining -= $take;
        }

        if ($remaining > 0.00005) {
            throw new RuntimeException('Insufficient stock for product #'.$productId.'.');
        }

        return $allocations;
    }

    /**
     * @return Collection<int, ProductBatch>
     */
    public function orderedBatchesWithStock(int $productId): Collection
    {
        return ProductBatch::query()
            ->where('product_id', $productId)
            ->where('quantity_on_hand', '>', 0)
            ->orderByRaw('expiry_date IS NULL')
            ->orderBy('expiry_date')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    /**
     * @param  Collection<int, ProductBatch>  $batches
     * @return Collection<int, ProductBatch>
     */
    private function prioritizeBatch(Collection $batches, int $preferredBatchId): Collection
    {
        $preferred = $batches->firstWhere('id', $preferredBatchId);

        if (! $preferred) {
            return $batches;
        }

        return $batches
            ->reject(fn (ProductBatch $batch) => (int) $batch->getKey() === $preferredBatchId)
            ->prepend($preferred)
            ->values();
    }
}
