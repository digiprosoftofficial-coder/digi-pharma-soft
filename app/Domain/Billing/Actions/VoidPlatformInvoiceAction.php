<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Models\PlatformInvoice;
use App\Models\User;

final class VoidPlatformInvoiceAction
{
    public function execute(PlatformInvoice $invoice, User $causer, ?string $reason = null): PlatformInvoice
    {
        if ($invoice->status !== PlatformInvoice::STATUS_OPEN) {
            throw new \InvalidArgumentException('Only open invoices can be voided.');
        }

        $invoice->status = PlatformInvoice::STATUS_VOID;
        $invoice->meta = array_merge($invoice->meta ?? [], [
            'void_reason' => $reason,
            'voided_at' => now()->toIso8601String(),
        ]);
        $invoice->save();

        $tenant = $invoice->tenant()->firstOrFail();

        activity()
            ->causedBy($causer)
            ->performedOn($tenant)
            ->tap(fn (\Spatie\Activitylog\Models\Activity $activity) => $activity->tenant_id = $tenant->getKey())
            ->withProperties([
                'invoice_no' => $invoice->invoice_no,
                'reason' => $reason,
            ])
            ->event('billing.invoice_voided')
            ->log('Subscription invoice voided');

        return $invoice->fresh();
    }
}
