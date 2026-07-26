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
use App\Support\Tenant\SupplierPaymentSettings;
use App\Support\Tenant\TenantFeatures;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
                $override = isset($line['conversion_factor']) ? (float) $line['conversion_factor'] : null;
                $baseUnit = $product->base_unit ?? 'strip';

                // Allow purchasing in units not yet saved on the product when this
                // receipt provides an explicit conversion factor.
                if ($sellUnit !== $baseUnit && ! $product->units->contains('sell_unit', $sellUnit)) {
                    if ($override === null || $override <= 0) {
                        throw ValidationException::withMessages([
                            'lines' => [__('purchases.unit_conversion_required', [
                                'product' => $product->name,
                                'unit' => $sellUnit,
                            ])],
                        ]);
                    }
                }

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
                    $factor,
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

            if ($paid > 0) {
                if ($paymentMethod === null || $paymentMethod === '') {
                    throw new RuntimeException(__('purchases.payment_method_required'));
                }

                $payment = PurchasePayment::query()->create([
                    'purchase_id' => $purchase->getKey(),
                    'paying_branch_id' => \branch_id() ?? $purchase->branch_id,
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
            $purchase = Purchase::query()->withoutGlobalScope('branch')->whereKey($purchase->getKey())->lockForUpdate()->firstOrFail();
            $due = (float) $purchase->due;
            $displayDue = round($due, 2);

            if ($amount <= 0) {
                throw new RuntimeException(__('purchases.payment_amount_invalid'));
            }

            if (round($amount, 2) > $displayDue + 0.0001) {
                throw new RuntimeException(__('purchases.payment_exceeds_due'));
            }

            $payingBranchId = \branch_id();
            if ($payingBranchId === null) {
                throw new RuntimeException(__('purchases.payment_branch_required'));
            }

            if (
                TenantFeatures::supplierBranchLedgerEnabled(tenant())
                && ! SupplierPaymentSettings::crossBranchEnabled(tenant())
                && (int) $payingBranchId !== (int) $purchase->branch_id
            ) {
                throw new RuntimeException(__('purchases.cross_branch_payment_disabled'));
            }

            $payment = $this->createPayment(
                $purchase,
                $method,
                $amount,
                $paidAt ?? now()->toDateString(),
                $reference,
                $notes,
                (int) $payingBranchId,
            );

            $payment->load('purchase');
            $this->vouchers->postPurchasePayment($payment);

            return $payment;
        });
    }

    public function voidPurchase(Purchase $purchase): Purchase
    {
        return DB::transaction(function () use ($purchase) {
            $purchase = Purchase::query()->withoutGlobalScope('branch')->whereKey($purchase->getKey())->lockForUpdate()->firstOrFail();

            if ($purchase->status === 'voided') {
                throw new RuntimeException(__('purchases.already_voided'));
            }

            $purchase->load(['lines', 'payments']);

            foreach ($purchase->payments as $payment) {
                $this->vouchers->reversePurchasePayment($payment);
                $payment->delete();
            }

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

            $purchase->update(['status' => 'voided']);
            $this->vouchers->reversePurchase($purchase);

            return $purchase->fresh(['lines', 'supplier', 'payments']);
        });
    }

    private function createPayment(
        Purchase $purchase,
        string $method,
        float $amount,
        string $paidAt,
        ?string $reference,
        ?string $notes,
        int $payingBranchId,
    ): PurchasePayment {
        $payment = PurchasePayment::query()->create([
            'purchase_id' => $purchase->getKey(),
            'paying_branch_id' => $payingBranchId,
            'method' => $method,
            'amount' => $amount,
            'paid_at' => $paidAt,
            'reference' => $reference,
            'notes' => $notes,
        ]);

        $purchase->paid = (string) ((float) $purchase->paid + $amount);
        $purchase->due = (string) max(0, (float) $purchase->due - $amount);
        $purchase->save();

        return $payment;
    }

    private function applyCatalogPrices(Product $product, string $sellUnit, float $conversionFactor, float $purchasePrice, ?float $salePrice): void
    {
        $baseUnit = $product->base_unit ?? 'strip';
        $factor = max(0.0001, $conversionFactor);
        $purchasePerBase = $sellUnit === $baseUnit ? $purchasePrice : $purchasePrice / $factor;
        $salePerBase = $salePrice !== null
            ? ($sellUnit === $baseUnit ? $salePrice : $salePrice / $factor)
            : null;

        $defaultPurchasePrice = $purchasePerBase;
        $defaultSalePrice = $salePerBase;

        $product->units->each(function ($unitRow) use ($baseUnit, $purchasePerBase, $salePerBase, &$defaultPurchasePrice, &$defaultSalePrice): void {
            $unitFactor = $unitRow->sell_unit === $baseUnit
                ? 1.0
                : max(0.0001, (float) $unitRow->conversion_factor);
            $updates = ['purchase_price' => $purchasePerBase * $unitFactor];
            if ($salePerBase !== null) {
                $updates['sale_price'] = $salePerBase * $unitFactor;
            }
            $unitRow->update($updates);

            if ($unitRow->is_default) {
                $defaultPurchasePrice = $updates['purchase_price'];
                $defaultSalePrice = $updates['sale_price'] ?? $defaultSalePrice;
            }
        });

        $productUpdates = ['purchase_price' => $defaultPurchasePrice];
        if ($defaultSalePrice !== null) {
            $productUpdates['sale_price'] = $defaultSalePrice;
        }
        $product->update($productUpdates);

        if ($salePerBase !== null) {
            $product->loadMissing('batches');
            foreach ($product->batches as $batch) {
                $storedFactor = $batch->pack_sell_unit && $batch->pack_conversion_factor !== null
                    ? max(0.0001, (float) $batch->pack_conversion_factor)
                    : 1.0;
                $batch->update(['sale_price' => $salePerBase * $storedFactor]);
            }
        }

        if (! $product->units->contains('sell_unit', $sellUnit) && $sellUnit === $baseUnit) {
            $updates = ['purchase_price' => $purchasePerBase];
            if ($salePerBase !== null) {
                $updates['sale_price'] = $salePerBase;
            }
            $product->update($updates);
        }
    }
}
