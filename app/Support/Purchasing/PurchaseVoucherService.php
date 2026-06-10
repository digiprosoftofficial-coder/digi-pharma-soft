<?php

namespace App\Support\Purchasing;

use App\Domain\Accounting\Models\LedgerAccount;
use App\Domain\Accounting\Models\LedgerEntry;
use App\Domain\Purchasing\Models\Purchase;
use App\Domain\Purchasing\Models\PurchasePayment;
use Illuminate\Support\Facades\DB;

final class PurchaseVoucherService
{
    public const INVENTORY_CODE = '1200';

    public const PAYABLE_CODE = '2000';

    public const CASH_CODE = '1000';

    public function postPurchase(Purchase $purchase): void
    {
        $inventory = $this->account(self::INVENTORY_CODE);
        $payable = $this->account(self::PAYABLE_CODE);
        if (! $inventory || ! $payable) {
            return;
        }

        $total = (float) $purchase->total;
        if ($total <= 0) {
            return;
        }

        DB::transaction(function () use ($purchase, $inventory, $payable, $total) {
            $this->entry($inventory, 'debit', $total, Purchase::class, $purchase->getKey(), "Purchase {$purchase->invoice_no}");
            $this->entry($payable, 'credit', $total, Purchase::class, $purchase->getKey(), "Purchase {$purchase->invoice_no}");
        });
    }

    public function postPurchasePayment(PurchasePayment $payment): void
    {
        $payable = $this->account(self::PAYABLE_CODE);
        $cash = $this->account(self::CASH_CODE);
        if (! $payable || ! $cash) {
            return;
        }

        $amount = (float) $payment->amount;
        if ($amount <= 0) {
            return;
        }

        $purchase = $payment->purchase;
        $memo = $purchase
            ? "Payment {$purchase->invoice_no} ({$payment->method})"
            : "Purchase payment #{$payment->getKey()}";

        DB::transaction(function () use ($payment, $payable, $cash, $amount, $memo) {
            $this->entry($payable, 'debit', $amount, PurchasePayment::class, $payment->getKey(), $memo);
            $this->entry($cash, 'credit', $amount, PurchasePayment::class, $payment->getKey(), $memo);
        });
    }

    public function reversePurchasePayment(PurchasePayment $payment): void
    {
        $payable = $this->account(self::PAYABLE_CODE);
        $cash = $this->account(self::CASH_CODE);
        if (! $payable || ! $cash) {
            return;
        }

        $amount = (float) $payment->amount;
        if ($amount <= 0) {
            return;
        }

        $purchase = $payment->purchase;
        $memo = $purchase
            ? "Void payment {$purchase->invoice_no}"
            : "Void purchase payment #{$payment->getKey()}";

        DB::transaction(function () use ($payment, $payable, $cash, $amount, $memo) {
            $this->entry($payable, 'credit', $amount, PurchasePayment::class, $payment->getKey(), $memo);
            $this->entry($cash, 'debit', $amount, PurchasePayment::class, $payment->getKey(), $memo);
        });
    }

    public function reversePurchase(Purchase $purchase): void
    {
        $inventory = $this->account(self::INVENTORY_CODE);
        $payable = $this->account(self::PAYABLE_CODE);
        if (! $inventory || ! $payable) {
            return;
        }

        $total = (float) $purchase->total;
        if ($total <= 0) {
            return;
        }

        DB::transaction(function () use ($purchase, $inventory, $payable, $total) {
            $this->entry($inventory, 'credit', $total, Purchase::class, $purchase->getKey(), "Void purchase {$purchase->invoice_no}");
            $this->entry($payable, 'debit', $total, Purchase::class, $purchase->getKey(), "Void purchase {$purchase->invoice_no}");
        });
    }

    private function account(string $code): ?LedgerAccount
    {
        return LedgerAccount::query()->where('code', $code)->first();
    }

    private function entry(
        LedgerAccount $account,
        string $direction,
        float $amount,
        string $referenceType,
        int $referenceId,
        string $memo,
    ): void {
        LedgerEntry::query()->create([
            'ledger_account_id' => $account->getKey(),
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'amount' => $amount,
            'direction' => $direction,
            'memo' => $memo,
            'posted_at' => now(),
        ]);
    }
}
