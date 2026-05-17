<?php

namespace App\Domain\Inventory\Services;

use App\Domain\Catalog\Models\ProductBatch;
use App\Domain\Inventory\Models\StockMovement;
use App\Domain\Inventory\Models\StockTransfer;
use App\Domain\Inventory\Models\StockTransferLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

final class StockTransferService
{
    /**
     * @param  array<int, array{from_batch_id:int, to_batch_id:int, quantity:float}>  $lines
     */
    public function recordTransfer(array $lines, ?string $notes = null): StockTransfer
    {
        return DB::transaction(function () use ($lines, $notes) {
            $transfer = StockTransfer::query()->create([
                'transfer_no' => 'TR-'.now()->format('Ymd').'-'.Str::upper(Str::random(5)),
                'transferred_at' => now(),
                'notes' => $notes,
                'status' => 'posted',
            ]);

            foreach ($lines as $line) {
                /** @var ProductBatch $from */
                $from = ProductBatch::query()->whereKey($line['from_batch_id'])->lockForUpdate()->firstOrFail();
                /** @var ProductBatch $to */
                $to = ProductBatch::query()->whereKey($line['to_batch_id'])->lockForUpdate()->firstOrFail();

                if ((int) $from->product_id !== (int) $to->product_id) {
                    throw new RuntimeException('Transfer batches must be the same product.');
                }

                $qty = (float) $line['quantity'];
                if ((float) $from->quantity_on_hand < $qty) {
                    throw new RuntimeException('Insufficient quantity on source batch.');
                }

                $from->quantity_on_hand = (string) ((float) $from->quantity_on_hand - $qty);
                $from->save();

                $to->quantity_on_hand = (string) ((float) $to->quantity_on_hand + $qty);
                $to->save();

                StockTransferLine::query()->create([
                    'stock_transfer_id' => $transfer->getKey(),
                    'from_batch_id' => $from->getKey(),
                    'to_batch_id' => $to->getKey(),
                    'quantity' => $qty,
                ]);

                StockMovement::query()->create([
                    'product_batch_id' => $from->getKey(),
                    'type' => 'transfer_out',
                    'reference_type' => StockTransfer::class,
                    'reference_id' => $transfer->getKey(),
                    'quantity_delta' => -1 * $qty,
                    'meta' => [],
                ]);

                StockMovement::query()->create([
                    'product_batch_id' => $to->getKey(),
                    'type' => 'transfer_in',
                    'reference_type' => StockTransfer::class,
                    'reference_id' => $transfer->getKey(),
                    'quantity_delta' => $qty,
                    'meta' => [],
                ]);
            }

            return $transfer;
        });
    }
}
