<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Models\PlatformInvoice;
use App\Models\User;
use App\Support\Platform\PlatformSettings;

final class MarkPlatformInvoiceFailedAction
{
    public function execute(PlatformInvoice $invoice, User $causer, ?string $reason = null): PlatformInvoice
    {
        if ($invoice->isPaid()) {
            throw new \InvalidArgumentException('Paid invoices cannot be marked as failed.');
        }

        $invoice->status = PlatformInvoice::STATUS_UNCOLLECTIBLE;
        $invoice->meta = array_merge($invoice->meta ?? [], [
            'failure_reason' => $reason,
            'failed_at' => now()->toIso8601String(),
        ]);
        $invoice->save();

        $tenant = $invoice->tenant()->firstOrFail();
        $graceDays = PlatformSettings::billingGraceDays();

        $tenant->billing_status = 'past_due';
        $tenant->payment_failed_at = now();
        $tenant->grace_period_ends_at = now()->addDays($graceDays);
        $tenant->save();

        activity()
            ->causedBy($causer)
            ->performedOn($tenant)
            ->tap(fn (\Spatie\Activitylog\Models\Activity $activity) => $activity->tenant_id = $tenant->getKey())
            ->withProperties([
                'invoice_no' => $invoice->invoice_no,
                'grace_days' => $graceDays,
                'reason' => $reason,
            ])
            ->event('billing.payment_failed')
            ->log('Subscription payment failed');

        return $invoice->fresh();
    }
}
