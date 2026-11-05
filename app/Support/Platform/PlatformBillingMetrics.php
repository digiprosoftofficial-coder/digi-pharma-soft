<?php

namespace App\Support\Platform;

use App\Domain\Billing\Models\PlatformInvoice;
use App\Domain\Billing\Models\TenantSubscription;
use App\Domain\Tenant\Models\Tenant;
final class PlatformBillingMetrics
{
    /**
     * @return array{
     *   mrr_cents: int,
     *   arr_cents: int,
     *   active_subscriptions: int,
     *   past_due_tenants: int,
     *   open_invoices: int,
     *   collected_this_month_cents: int,
     *   currency: string
     * }
     */
    public static function snapshot(): array
    {
        $currency = PlatformSettings::defaultCurrency();

        $activeSubs = TenantSubscription::query()
            ->join('tenants', 'tenants.id', '=', 'tenant_subscriptions.tenant_id')
            ->join('subscription_plans', 'subscription_plans.id', '=', 'tenant_subscriptions.subscription_plan_id')
            ->where('tenant_subscriptions.status', 'active')
            ->whereNull('tenants.suspended_at')
            ->where('tenants.is_active', true)
            ->where(function ($q) {
                $q->whereNull('tenant_subscriptions.ends_at')
                    ->orWhere('tenant_subscriptions.ends_at', '>=', now());
            })
            ->get([
                'tenant_subscriptions.billing_interval',
                'subscription_plans.price_cents',
                'subscription_plans.monthly_price_cents',
                'subscription_plans.yearly_price_cents',
            ]);

        $mrrCents = (int) $activeSubs->sum(function ($row) {
            $interval = $row->billing_interval ?: 'monthly';
            if ($interval === 'yearly') {
                $yearly = (int) ($row->yearly_price_cents ?: $row->price_cents);

                return (int) round($yearly / 12);
            }

            return (int) ($row->monthly_price_cents ?: $row->price_cents);
        });

        $pastDue = Tenant::query()
            ->where('billing_status', 'past_due')
            ->whereNull('suspended_at')
            ->count();

        $openInvoices = PlatformInvoice::query()
            ->where('status', PlatformInvoice::STATUS_OPEN)
            ->count();

        $collectedThisMonth = (int) PlatformInvoice::query()
            ->where('status', PlatformInvoice::STATUS_PAID)
            ->where('paid_at', '>=', now()->startOfMonth())
            ->sum('amount_cents');

        $activeSubscriptions = (int) TenantSubscription::query()
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->count();

        return [
            'mrr_cents' => $mrrCents,
            'arr_cents' => $mrrCents * 12,
            'active_subscriptions' => $activeSubscriptions,
            'past_due_tenants' => $pastDue,
            'open_invoices' => $openInvoices,
            'collected_this_month_cents' => $collectedThisMonth,
            'currency' => $currency,
        ];
    }
}
