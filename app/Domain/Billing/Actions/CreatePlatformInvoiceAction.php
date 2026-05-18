<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Models\PlatformInvoice;
use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use App\Support\Platform\PlatformSettings;
use Illuminate\Support\Str;

final class CreatePlatformInvoiceAction
{
    /**
     * @param  array{
     *   amount_cents?: int,
     *   subscription_plan_id?: int|null,
     *   due_at?: string|null,
     *   period_start?: string|null,
     *   period_end?: string|null,
     *   provider?: string,
     *   provider_reference?: string|null,
     * }  $data
     */
    public function execute(Tenant $tenant, array $data, User $causer): PlatformInvoice
    {
        $tenant->loadMissing('activeSubscription.plan');

        $plan = null;
        if (! empty($data['subscription_plan_id'])) {
            $plan = SubscriptionPlan::query()->findOrFail($data['subscription_plan_id']);
        } elseif ($tenant->activeSubscription?->subscription_plan_id) {
            $plan = $tenant->activeSubscription->plan;
        }

        $amountCents = (int) ($data['amount_cents'] ?? $plan?->price_cents ?? 0);
        if ($amountCents <= 0) {
            throw new \InvalidArgumentException('Invoice amount must be greater than zero.');
        }

        $invoice = PlatformInvoice::query()->create([
            'tenant_id' => $tenant->getKey(),
            'subscription_plan_id' => $plan?->getKey(),
            'invoice_no' => $this->nextInvoiceNo($tenant),
            'amount_cents' => $amountCents,
            'currency' => PlatformSettings::defaultCurrency(),
            'status' => PlatformInvoice::STATUS_OPEN,
            'provider' => $data['provider'] ?? 'manual',
            'provider_reference' => $data['provider_reference'] ?? null,
            'period_start' => $data['period_start'] ?? now()->startOfMonth(),
            'period_end' => $data['period_end'] ?? now()->endOfMonth(),
            'due_at' => $data['due_at'] ?? now()->addDays(7),
        ]);

        activity()
            ->causedBy($causer)
            ->performedOn($tenant)
            ->tap(fn (\Spatie\Activitylog\Models\Activity $activity) => $activity->tenant_id = $tenant->getKey())
            ->withProperties(['invoice_no' => $invoice->invoice_no, 'amount_cents' => $amountCents])
            ->event('billing.invoice_created')
            ->log('Subscription invoice created');

        return $invoice;
    }

    private function nextInvoiceNo(Tenant $tenant): string
    {
        $prefix = 'INV-'.now()->format('Ym').'-';
        $last = PlatformInvoice::query()
            ->where('tenant_id', $tenant->getKey())
            ->where('invoice_no', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('invoice_no');

        $seq = 1;
        if (is_string($last) && Str::contains($last, '-')) {
            $seq = ((int) last(explode('-', $last))) + 1;
        }

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
