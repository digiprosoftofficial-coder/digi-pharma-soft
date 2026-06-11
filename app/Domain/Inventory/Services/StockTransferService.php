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
     * @param  array<int, array{from_batch_id:int, to_batch_id?:int, to_branch_id?:int, quantity:float}>  $lines
     */
    public function recordTransfer(array $lines, ?string $notes = null): StockTransfer
    {
        return DB::transaction(function () use ($lines, $notes) {
            $firstLine = $lines[0] ?? null;
            $fromBatch = $firstLine
                ? ProductBatch::query()->withoutGlobalScope('branch')->whereKey($firstLine['from_batch_id'])->first()
                : null;

            $transfer = StockTransfer::query()->create([
                'from_branch_id' => $fromBatch?->branch_id ?? \branch_id(),
                'to_branch_id' => $firstLine['to_branch_id'] ?? $fromBatch?->branch_id ?? \branch_id(),
                'transfer_no' => 'TR-'.now()->format('Ymd').'-'.Str::upper(Str::random(5)),
                'transferred_at' => now(),
                'notes' => $notes,
                'status' => 'posted',
            ]);

            foreach ($lines as $line) {
                /** @var ProductBatch $from */
                $from = ProductBatch::query()->withoutGlobalScope('branch')->whereKey($line['from_batch_id'])->lockForUpdate()->firstOrFail();

                $to = isset($line['to_batch_id'])
                    ? ProductBatch::query()->withoutGlobalScope('branch')->whereKey($line['to_batch_id'])->lockForUpdate()->firstOrFail()
                    : $this->resolveTargetBatch($from, (int) ($line['to_branch_id'] ?? $transfer->to_branch_id));

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

                StockMovement::query()->withoutGlobalScope('branch')->create([
                    'branch_id' => $from->branch_id,
                    'product_batch_id' => $from->getKey(),
                    'type' => 'transfer_out',
                    'reference_type' => StockTransfer::class,
                    'reference_id' => $transfer->getKey(),
                    'quantity_delta' => -1 * $qty,
                    'meta' => [],
                ]);

                StockMovement::query()->withoutGlobalScope('branch')->create([
                    'branch_id' => $to->branch_id,
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

    private function resolveTargetBatch(ProductBatch $from, int $toBranchId): ProductBatch
    {
        $existing = ProductBatch::query()
            ->withoutGlobalScope('branch')
            ->where('branch_id', $toBranchId)
            ->where('product_id', $from->product_id)
            ->where('batch_no', $from->batch_no)
            ->lockForUpdate()
            ->first();

        if ($existing) {
            return $existing;
        }

        return ProductBatch::query()->withoutGlobalScope('branch')->create([
            'branch_id' => $toBranchId,
            'product_id' => $from->product_id,
            'batch_no' => $from->batch_no,
            'expiry_date' => $from->expiry_date,
            'manufactured_at' => $from->manufactured_at,
            'quantity_on_hand' => 0,
            'purchase_unit_cost' => $from->purchase_unit_cost,
            'sale_price' => $from->sale_price,
            'storage_location_id' => $from->storage_location_id,
            'pack_sell_unit' => $from->pack_sell_unit,
            'pack_conversion_factor' => $from->pack_conversion_factor,
        ]);
    }
}
