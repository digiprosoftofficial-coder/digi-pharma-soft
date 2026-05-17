<?php

namespace App\Domain\Sales\Services;

use App\Domain\Catalog\Models\ProductBatch;
use App\Domain\Inventory\Models\StockMovement;
use App\Domain\Sales\Models\Customer;
use App\Domain\Sales\Models\DiscountCoupon;
use App\Domain\Sales\Models\Sale;
use App\Domain\Sales\Models\SaleLine;
use App\Domain\Sales\Models\SalePayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

final class SaleService
{
    /**
     * @param  array<int, array{product_batch_id:int,quantity:float,unit_price:float}>  $lines
     * @param  array<int, array{method:string,amount:float}>  $payments
     */
    public function checkout(
        ?int $customerId,
        array $lines,
        array $payments,
        float $discount = 0,
        float $tax = 0,
        ?string $couponCode = null,
    ): Sale {
        return DB::transaction(function () use ($customerId, $lines, $payments, $discount, $tax, $couponCode) {
            $subtotal = collect($lines)->sum(fn (array $l) => $l['quantity'] * $l['unit_price']);
            $couponDiscount = 0.0;
            if ($couponCode) {
                $coupon = DiscountCoupon::query()
                    ->where('code', strtoupper(trim($couponCode)))
                    ->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                    })
                    ->first();
                if ($coupon) {
                    $couponDiscount = round($subtotal * ((float) $coupon->percent_off / 100), 4);
                }
            }
            $discount = round($discount + $couponDiscount, 4);
            $total = max(0, $subtotal - $discount + $tax);
            if (count($payments) === 1 && (float) $payments[0]['amount'] >= $subtotal - 0.0001) {
                $payments = [
                    ['method' => $payments[0]['method'], 'amount' => $total],
                ];
            }
            $paid = collect($payments)->sum(fn (array $p) => $p['amount']);
            $due = max(0, $total - $paid);

            $sale = Sale::query()->create([
                'customer_id' => $customerId,
                'invoice_no' => $this->nextInvoiceNo(),
                'sold_at' => now(),
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'total' => $total,
                'paid' => $paid,
                'due' => $due,
                'status' => 'posted',
            ]);

            foreach ($lines as $line) {
                /** @var ProductBatch $batch */
                $batch = ProductBatch::query()->whereKey($line['product_batch_id'])->lockForUpdate()->firstOrFail();
                if ((float) $batch->quantity_on_hand < (float) $line['quantity']) {
                    throw new RuntimeException('Insufficient stock for batch '.$batch->getKey());
                }

                $batch->quantity_on_hand = (string) ((float) $batch->quantity_on_hand - (float) $line['quantity']);
                $batch->save();

                $lineTotal = $line['quantity'] * $line['unit_price'];

                SaleLine::query()->create([
                    'sale_id' => $sale->getKey(),
                    'product_id' => $batch->product_id,
                    'product_batch_id' => $batch->getKey(),
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'line_total' => $lineTotal,
                ]);

                StockMovement::query()->create([
                    'product_batch_id' => $batch->getKey(),
                    'type' => 'sale',
                    'reference_type' => Sale::class,
                    'reference_id' => $sale->getKey(),
                    'quantity_delta' => -1 * (float) $line['quantity'],
                    'meta' => ['sale_line' => true],
                ]);
            }

            foreach ($payments as $payment) {
                SalePayment::query()->create([
                    'sale_id' => $sale->getKey(),
                    'method' => $payment['method'],
                    'amount' => $payment['amount'],
                ]);
            }

            if ($customerId) {
                $customer = Customer::query()->whereKey($customerId)->lockForUpdate()->first();
                if ($customer) {
                    $customer->balance_due = (string) ((float) $customer->balance_due + $due);
                    $customer->save();
                }
            }

            return $sale->load(['lines', 'payments']);
        });
    }

    private function nextInvoiceNo(): string
    {
        return 'INV-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
    }
}
