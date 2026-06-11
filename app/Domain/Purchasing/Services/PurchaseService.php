<?php

namespace App\Domain\Purchasing\Services;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductBatch;
use App\Domain\Inventory\Models\StockMovement;
use App\Domain\Purchasing\Models\Purchase;
use App\Domain\Purchasing\Models\PurchaseLine;
use App\Domain\Purchasing\Models\PurchasePayment;
use App\Domain\Purchasing\Models\Supplier;
use App\Support\Catalog\ProductUnitResolver;
use App\Support\Purchasing\PurchaseVoucherService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

final class PurchaseService
{
    public function __construct(private readonly PurchaseVoucherService $vouchers) {}
    /**
     * @param  array<int, array{product_id:int,batch_no:string,expiry_date?:string,manufactured_at?:string,quantity:float,unit_cost:float,sale_price?:float|null,sell_unit?:string,conversion_factor?:float|null}>  $lines
     */
    public function recordPurchase(
        Supplier $supplier,
        string $invoiceNo,
        string $purchasedAt,
        array $lines,
        float $tax = 0,
        float $discount = 0,
        float $paid = 0,
        ?string $paymentMethod = null,
        ?string $notes = null,
    ): Purchase {
        return DB::transaction(function () use ($supplier, $invoiceNo, $purchasedAt, $lines, $tax, $discount, $paid, $paymentMethod, $notes) {
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
                'notes' => $notes,
            ]);

            foreach ($lines as $line) {
                $product = Product::query()->with('units')->findOrFail($line['product_id']);
                $sellUnit = (string) ($line['sell_unit'] ?? $product->base_unit ?? 'strip');

                try {
                    $unitConfig = ProductUnitResolver::forProduct($product, $sellUnit);
                } catch (InvalidArgumentException) {
                    $unitConfig = $product->defaultUnit();
                    $sellUnit = $unitConfig?->sell_unit ?? $sellUnit;
                }

                $override = isset($line['conversion_factor']) ? (float) $line['conversion_factor'] : null;
                $factor = ProductUnitResolver::resolveConversionFactor($product, $sellUnit, $override);
                $quantityBase = (float) ProductUnitResolver::quantityBase($line['quantity'], $factor);
                $lineTotal = $line['quantity'] * $line['unit_cost'];

                PurchaseLine::query()->create([
                    'purchase_id' => $purchase->getKey(),
                    'product_id' => $line['product_id'],
                    'batch_no' => $line['batch_no'],
                    'expiry_date' => $line['expiry_date'] ?? null,
                    'manufactured_at' => $line['manufactured_at'] ?? null,
                    'quantity' => $line['quantity'],
                    'sell_unit' => $sellUnit,
                    'conversion_factor' => $factor,
                    'quantity_base' => $quantityBase,
                    'unit_cost' => $line['unit_cost'],
                    'sale_price' => $line['sale_price'] ?? null,
                    'line_total' => $lineTotal,
                ]);

                $batch = ProductBatch::query()->firstOrCreate(
                    [
                        'branch_id' => \branch_id(),
                        'product_id' => $line['product_id'],
                        'batch_no' => $line['batch_no'],
                    ],
                    [
                        'expiry_date' => $line['expiry_date'] ?? null,
                        'manufactured_at' => $line['manufactured_at'] ?? null,
                        'quantity_on_hand' => 0,
                        'purchase_unit_cost' => $line['unit_cost'],
                    ],
                );

                $batch->quantity_on_hand = (string) ((float) $batch->quantity_on_hand + $quantityBase);
                $batch->purchase_unit_cost = $line['unit_cost'];

                if (filled($line['expiry_date'] ?? null)) {
                    $batch->expiry_date = $line['expiry_date'];
                }

                if (filled($line['manufactured_at'] ?? null)) {
                    $batch->manufactured_at = $line['manufactured_at'];
                }

                $locationId = $line['storage_location_id'] ?? null;
                if ($locationId !== null) {
                    $batch->storage_location_id = $locationId;
                } elseif ($batch->storage_location_id === null && $product->storage_location_id !== null) {
                    $batch->storage_location_id = $product->storage_location_id;
                }

                $baseUnit = $product->base_unit ?? 'strip';
                if ($sellUnit !== $baseUnit) {
                    $batch->pack_sell_unit = $sellUnit;
                    $batch->pack_conversion_factor = $factor;
                }

                if (isset($line['sale_price']) && $line['sale_price'] !== null && $line['sale_price'] !== '') {
                    $batch->sale_price = $line['sale_price'];
                }

                $batch->save();

                $this->applyCatalogPrices(
                    $product,
                    $sellUnit,
                    (float) $line['unit_cost'],
                    isset($line['sale_price']) ? (float) $line['sale_price'] : null,
                );

                StockMovement::query()->create([
                    'product_batch_id' => $batch->getKey(),
                    'type' => 'purchase',
                    'reference_type' => Purchase::class,
                    'reference_id' => $purchase->getKey(),
                    'quantity_delta' => $quantityBase,
                    'meta' => [
                        'purchase_line' => true,
                        'sell_unit' => $sellUnit,
                        'quantity' => $line['quantity'],
                    ],
                ]);
            }

            $supplier->balance_due = (string) ((float) $supplier->balance_due + $due);
            $supplier->save();

            if ($paid > 0) {
                if ($paymentMethod === null || $paymentMethod === '') {
                    throw new RuntimeException(__('purchases.payment_method_required'));
                }

                $payment = PurchasePayment::query()->create([
                    'purchase_id' => $purchase->getKey(),
                    'method' => $paymentMethod,
                    'amount' => $paid,
                    'paid_at' => $purchasedAt,
                ]);
                $payment->setRelation('purchase', $purchase);
                $this->vouchers->postPurchasePayment($payment);
            }

            $this->vouchers->postPurchase($purchase);

            return $purchase->load(['lines', 'payments']);
        });
    }

    public function recordPayment(
        Purchase $purchase,
        string $method,
        float $amount,
        ?string $paidAt = null,
        ?string $reference = null,
        ?string $notes = null,
    ): PurchasePayment {
        return DB::transaction(function () use ($purchase, $method, $amount, $paidAt, $reference, $notes) {
            $purchase = Purchase::query()->whereKey($purchase->getKey())->lockForUpdate()->firstOrFail();
            $supplier = Supplier::query()->whereKey($purchase->supplier_id)->lockForUpdate()->firstOrFail();
            $due = (float) $purchase->due;

            if ($amount <= 0) {
                throw new RuntimeException(__('purchases.payment_amount_invalid'));
            }

            if ($amount > $due + 0.0001) {
                throw new RuntimeException(__('purchases.payment_exceeds_due'));
            }

            $payment = $this->createPayment(
                $purchase,
                $supplier,
                $method,
                $amount,
                $paidAt ?? now()->toDateString(),
                $reference,
                $notes,
            );

            $payment->load('purchase');
            $this->vouchers->postPurchasePayment($payment);

            return $payment;
        });
    }

    public function voidPurchase(Purchase $purchase): Purchase
    {
        return DB::transaction(function () use ($purchase) {
            $purchase = Purchase::query()->whereKey($purchase->getKey())->lockForUpdate()->firstOrFail();

            if ($purchase->status === 'voided') {
                throw new RuntimeException(__('purchases.already_voided'));
            }

            $purchase->load(['lines', 'payments']);

            foreach ($purchase->payments as $payment) {
                $this->vouchers->reversePurchasePayment($payment);
                $payment->delete();
            }
            $supplier = Supplier::query()->whereKey($purchase->supplier_id)->lockForUpdate()->firstOrFail();

            foreach ($purchase->lines as $line) {
                $batch = ProductBatch::query()
                    ->withoutGlobalScope('branch')
                    ->where('branch_id', $purchase->branch_id)
                    ->where('product_id', $line->product_id)
                    ->where('batch_no', $line->batch_no)
                    ->lockForUpdate()
                    ->first();

                if (! $batch) {
                    continue;
                }

                $quantityBase = (float) $line->quantity_base;
                if ((float) $batch->quantity_on_hand < $quantityBase) {
                    throw new RuntimeException(__('purchases.cannot_void_insufficient_stock'));
                }

                $batch->quantity_on_hand = (string) ((float) $batch->quantity_on_hand - $quantityBase);
                $batch->save();

                StockMovement::query()->create([
                    'product_batch_id' => $batch->getKey(),
                    'type' => 'purchase_void',
                    'reference_type' => Purchase::class,
                    'reference_id' => $purchase->getKey(),
                    'quantity_delta' => -$quantityBase,
                    'meta' => ['purchase_line_id' => $line->getKey()],
                ]);
            }

            $supplier->balance_due = (string) max(0, (float) $supplier->balance_due - (float) $purchase->due);
            $supplier->save();

            $purchase->update(['status' => 'voided']);
            $this->vouchers->reversePurchase($purchase);

            return $purchase->fresh(['lines', 'supplier', 'payments']);
        });
    }

    private function createPayment(
        Purchase $purchase,
        Supplier $supplier,
        string $method,
        float $amount,
        string $paidAt,
        ?string $reference = null,
        ?string $notes = null,
    ): PurchasePayment {
        $payment = PurchasePayment::query()->create([
            'purchase_id' => $purchase->getKey(),
            'method' => $method,
            'amount' => $amount,
            'paid_at' => $paidAt,
            'reference' => $reference,
            'notes' => $notes,
        ]);

        $purchase->paid = (string) ((float) $purchase->paid + $amount);
        $purchase->due = (string) max(0, (float) $purchase->due - $amount);
        $purchase->save();

        $supplier->balance_due = (string) max(0, (float) $supplier->balance_due - $amount);
        $supplier->save();

        return $payment;
    }

    private function applyCatalogPrices(Product $product, string $sellUnit, float $purchasePrice, ?float $salePrice): void
    {
        $unitRow = $product->units->firstWhere('sell_unit', $sellUnit);
        $baseUnit = $product->base_unit ?? 'strip';

        if ($unitRow) {
            $unitRow->update(['purchase_price' => $purchasePrice]);
            if ($salePrice !== null) {
                $unitRow->update(['sale_price' => $salePrice]);
            }

            return;
        }

        if ($sellUnit === $baseUnit) {
            $updates = ['purchase_price' => $purchasePrice];
            if ($salePrice !== null) {
                $updates['sale_price'] = $salePrice;
            }
            $product->update($updates);
        }
    }
}
