<?php

namespace App\Domain\Purchasing\Services;

use App\Domain\Catalog\Models\ProductBatch;
use App\Domain\Inventory\Models\StockMovement;
use App\Domain\Purchasing\Models\Purchase;
use App\Domain\Purchasing\Models\PurchaseLine;
use App\Domain\Purchasing\Models\Supplier;
use Illuminate\Support\Facades\DB;

final class PurchaseService
{
    /**
     * @param  array<int, array{product_id:int,batch_no:string,expiry_date?:string,quantity:float,unit_cost:float}>  $lines
     */
    public function recordPurchase(
        Supplier $supplier,
        string $invoiceNo,
        string $purchasedAt,
        array $lines,
        float $tax = 0,
        float $discount = 0,
        float $paid = 0,
    ): Purchase {
        return DB::transaction(function () use ($supplier, $invoiceNo, $purchasedAt, $lines, $tax, $discount, $paid) {
            $subtotal = collect($lines)->sum(fn (array $l) => $l['quantity'] * $l['unit_cost']);
            $total = max(0, $subtotal + $tax - $discount);
            $due = max(0, $total - $paid);

            $purchase = Purchase::query()->create([
                'supplier_id' => $supplier->getKey(),
                'invoice_no' => $invoiceNo,
                'purchased_at' => $purchasedAt,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'discount' => $discount,
                'total' => $total,
                'paid' => $paid,
                'due' => $due,
                'status' => 'posted',
            ]);

            foreach ($lines as $line) {
                $lineTotal = $line['quantity'] * $line['unit_cost'];
                PurchaseLine::query()->create([
                    'purchase_id' => $purchase->getKey(),
                    'product_id' => $line['product_id'],
                    'batch_no' => $line['batch_no'],
                    'expiry_date' => $line['expiry_date'] ?? null,
                    'quantity' => $line['quantity'],
                    'unit_cost' => $line['unit_cost'],
                    'line_total' => $lineTotal,
                ]);

                $batch = ProductBatch::query()->firstOrCreate(
                    [
                        'product_id' => $line['product_id'],
                        'batch_no' => $line['batch_no'],
                    ],
                    [
                        'expiry_date' => $line['expiry_date'] ?? null,
                        'quantity_on_hand' => 0,
                        'purchase_unit_cost' => $line['unit_cost'],
                    ],
                );

                $batch->quantity_on_hand = (string) ((float) $batch->quantity_on_hand + (float) $line['quantity']);
                $batch->purchase_unit_cost = $line['unit_cost'];
                $batch->save();

                StockMovement::query()->create([
                    'product_batch_id' => $batch->getKey(),
                    'type' => 'purchase',
                    'reference_type' => Purchase::class,
                    'reference_id' => $purchase->getKey(),
                    'quantity_delta' => $line['quantity'],
                    'meta' => ['purchase_line' => true],
                ]);
            }

            $supplier->balance_due = (string) ((float) $supplier->balance_due + $due);
            $supplier->save();

            return $purchase->load('lines');
        });
    }
}
