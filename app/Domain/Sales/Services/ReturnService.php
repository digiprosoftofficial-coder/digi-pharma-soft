<?php

namespace App\Domain\Sales\Services;

use App\Domain\Catalog\Models\ProductBatch;
use App\Domain\Inventory\Models\StockMovement;
use App\Domain\Sales\Models\SaleReturn;
use App\Domain\Sales\Models\SaleReturnLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

final class ReturnService
{
    /**
     * @param  array<int, array{product_batch_id:int, quantity:float, unit_price:float}>  $lines
     */
    public function recordReturn(?int $saleId, array $lines, ?string $notes = null): SaleReturn
    {
        return DB::transaction(function () use ($saleId, $lines, $notes) {
            $total = collect($lines)->sum(fn (array $l) => $l['quantity'] * $l['unit_price']);

            $return = SaleReturn::query()->create([
                'sale_id' => $saleId,
                'reference_no' => 'RT-'.now()->format('Ymd').'-'.Str::upper(Str::random(5)),
                'returned_at' => now(),
                'total_refund' => $total,
                'notes' => $notes,
                'status' => 'posted',
            ]);

            foreach ($lines as $line) {
                /** @var ProductBatch $batch */
                $batch = ProductBatch::query()->whereKey($line['product_batch_id'])->lockForUpdate()->firstOrFail();
                $lineTotal = $line['quantity'] * $line['unit_price'];

                SaleReturnLine::query()->create([
                    'sale_return_id' => $return->getKey(),
                    'product_id' => $batch->product_id,
                    'product_batch_id' => $batch->getKey(),
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'line_total' => $lineTotal,
                ]);

                $batch->quantity_on_hand = (string) ((float) $batch->quantity_on_hand + (float) $line['quantity']);
                $batch->save();

                StockMovement::query()->create([
                    'product_batch_id' => $batch->getKey(),
                    'type' => 'sale_return',
                    'reference_type' => SaleReturn::class,
                    'reference_id' => $return->getKey(),
                    'quantity_delta' => (float) $line['quantity'],
                    'meta' => ['sale_return_line' => true],
                ]);
            }

            return $return;
        });
    }
}
