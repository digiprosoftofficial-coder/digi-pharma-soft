<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Models\PlatformInvoice;
use App\Domain\Billing\Models\SubscriptionPlan;
use App\Models\User;

final class UpdatePlatformInvoiceAction
{
    /**
     * @param  array{
     *   amount_cents?: int|null,
     *   subscription_plan_id?: int|null,
     *   due_at?: string|null,
     * }  $data
     */
    public function execute(PlatformInvoice $invoice, array $data, User $causer): PlatformInvoice
    {
        if ($invoice->status !== PlatformInvoice::STATUS_OPEN) {
            throw new \InvalidArgumentException('Only open invoices can be edited.');
        }

        $plan = null;
        if (array_key_exists('subscription_plan_id', $data) && $data['subscription_plan_id'] !== null) {
            $plan = SubscriptionPlan::query()->findOrFail($data['subscription_plan_id']);
            $invoice->subscription_plan_id = $plan->getKey();
        }

        if (array_key_exists('amount_cents', $data) && $data['amount_cents'] !== null) {
            $amountCents = (int) $data['amount_cents'];
            if ($amountCents <= 0) {
                throw new \InvalidArgumentException('Invoice amount must be greater than zero.');
            }
            $invoice->amount_cents = $amountCents;
        } elseif ($plan !== null) {
            $invoice->amount_cents = (int) $plan->price_cents;
        }

        if (array_key_exists('due_at', $data)) {
            $invoice->due_at = $data['due_at'] !== null && $data['due_at'] !== ''
                ? \Illuminate\Support\Carbon::parse($data['due_at'])
                : null;
        }

        $invoice->save();

        $tenant = $invoice->tenant()->firstOrFail();

        activity()
            ->causedBy($causer)
            ->performedOn($tenant)
            ->tap(fn (\Spatie\Activitylog\Models\Activity $activity) => $activity->tenant_id = $tenant->getKey())
            ->withProperties([
                'invoice_no' => $invoice->invoice_no,
                'amount_cents' => $invoice->amount_cents,
            ])
            ->event('billing.invoice_updated')
            ->log('Subscription invoice updated');

        return $invoice->fresh(['tenant', 'plan']);
    }
}
