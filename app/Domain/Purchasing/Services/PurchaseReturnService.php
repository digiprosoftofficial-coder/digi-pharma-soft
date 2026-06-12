<?php

namespace App\Domain\Purchasing\Services;

use App\Domain\Catalog\Models\ProductBatch;
use App\Domain\Inventory\Models\StockMovement;
use App\Domain\Purchasing\Models\PurchaseReturn;
use App\Domain\Purchasing\Models\PurchaseReturnLine;
use App\Domain\Purchasing\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

final class PurchaseReturnService
{
    /**
     * @param  array<int, array{product_batch_id:int, quantity:float, unit_cost:float}>  $lines
     */
    public function recordReturn(
        Supplier $supplier,
        array $lines,
        ?int $purchaseId = null,
        ?string $notes = null,
    ): PurchaseReturn {
        return DB::transaction(function () use ($supplier, $lines, $purchaseId, $notes) {
            $total = collect($lines)->sum(fn (array $l) => $l['quantity'] * $l['unit_cost']);

            $return = PurchaseReturn::query()->create([
                'purchase_id' => $purchaseId,
                'supplier_id' => $supplier->getKey(),
                'reference_no' => 'PR-'.now()->format('Ymd').'-'.Str::upper(Str::random(5)),
                'returned_at' => now(),
                'total_credit' => $total,
                'notes' => $notes,
                'status' => 'posted',
            ]);

            foreach ($lines as $line) {
                /** @var ProductBatch $batch */
                $batch = ProductBatch::query()->whereKey($line['product_batch_id'])->lockForUpdate()->firstOrFail();
                $qty = (float) $line['quantity'];

                if ($qty <= 0) {
                    throw new RuntimeException(__('purchases.return_qty_invalid'));
                }

                if ((float) $batch->quantity_on_hand < $qty) {
                    throw new RuntimeException(__('purchases.return_insufficient_stock'));
                }

                $lineTotal = $qty * (float) $line['unit_cost'];

                PurchaseReturnLine::query()->create([
                    'purchase_return_id' => $return->getKey(),
                    'product_id' => $batch->product_id,
                    'product_batch_id' => $batch->getKey(),
                    'quantity' => $qty,
                    'unit_cost' => $line['unit_cost'],
                    'line_total' => $lineTotal,
                ]);

                $batch->quantity_on_hand = (string) ((float) $batch->quantity_on_hand - $qty);
                $batch->save();

                StockMovement::query()->create([
                    'product_batch_id' => $batch->getKey(),
                    'type' => 'purchase_return',
                    'reference_type' => PurchaseReturn::class,
                    'reference_id' => $return->getKey(),
                    'quantity_delta' => -$qty,
                    'meta' => ['purchase_return_line' => true],
                ]);
            }

            return $return->load(['lines.product', 'lines.batch', 'supplier']);
        });
    }
}
