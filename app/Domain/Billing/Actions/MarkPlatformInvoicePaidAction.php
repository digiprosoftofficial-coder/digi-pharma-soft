<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Models\PlatformInvoice;
use App\Models\User;

final class MarkPlatformInvoicePaidAction
{
    public function execute(PlatformInvoice $invoice, User $causer, ?string $providerReference = null): PlatformInvoice
    {
        if ($invoice->isPaid()) {
            return $invoice;
        }

        $invoice->status = PlatformInvoice::STATUS_PAID;
        $invoice->paid_at = now();
        if ($providerReference) {
            $invoice->provider_reference = $providerReference;
        }
        $invoice->save();

        $tenant = $invoice->tenant()->firstOrFail();
        $tenant->billing_status = 'active';
        $tenant->payment_failed_at = null;
        $tenant->grace_period_ends_at = null;

        if ($invoice->period_end) {
            $tenant->subscription_ends_at = $invoice->period_end;
        } elseif ($tenant->subscription_ends_at === null || $tenant->subscription_ends_at->isPast()) {
            $tenant->subscription_ends_at = now()->addMonth();
        }

        $tenant->save();

        activity()
            ->causedBy($causer)
            ->performedOn($tenant)
            ->tap(fn (\Spatie\Activitylog\Models\Activity $activity) => $activity->tenant_id = $tenant->getKey())
            ->withProperties(['invoice_no' => $invoice->invoice_no])
            ->event('billing.invoice_paid')
            ->log('Subscription invoice paid');

        return $invoice->fresh();
    }
}
