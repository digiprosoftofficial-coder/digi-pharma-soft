<?php

namespace App\Support\Platform;

use App\Domain\Billing\Models\PlatformPayment;
use App\Domain\Billing\Services\PlatformPaymentService;
use App\Domain\Crm\Models\DemoRequest;
use App\Domain\Crm\Models\Lead;
use App\Domain\Crm\Services\DemoRequestService;
use App\Domain\Crm\Services\LeadService;
use App\Domain\Tenant\Models\Tenant;

final class PlatformGrowthMetrics
{
    /**
     * @return array<string, mixed>
     */
    public static function snapshot(): array
    {
        $leads = app(LeadService::class);
        $demos = app(DemoRequestService::class);
        $payments = app(PlatformPaymentService::class);

        $trialCustomers = Tenant::query()
            ->whereNull('suspended_at')
            ->where('is_active', true)
            ->where('billing_status', 'trialing')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>', now())
            ->count();

        $expiredSubscriptions = Tenant::query()
            ->whereNull('suspended_at')
            ->whereNotNull('subscription_ends_at')
            ->where('subscription_ends_at', '<', now())
            ->count();

        $upcomingRenewals = Tenant::query()
            ->whereNull('suspended_at')
            ->where('is_active', true)
            ->whereBetween('subscription_ends_at', [now(), now()->addDays(14)])
            ->count();

        $yearlyRevenueCents = (int) \App\Domain\Billing\Models\PlatformInvoice::query()
            ->where('status', \App\Domain\Billing\Models\PlatformInvoice::STATUS_PAID)
            ->where('paid_at', '>=', now()->startOfYear())
            ->sum('amount_cents');

        return [
            'leads' => $leads->dashboardStats(),
            'lead_conversion_rate' => $leads->conversionRate(),
            'demos' => $demos->dashboardStats(),
            'payments' => $payments->dashboardStats(),
            'trial_customers' => $trialCustomers,
            'expired_subscriptions' => $expiredSubscriptions,
            'upcoming_renewals' => $upcomingRenewals,
            'yearly_revenue_cents' => $yearlyRevenueCents,
            'new_leads_7d' => Lead::query()->where('created_at', '>=', now()->subDays(7))->count(),
            'new_demos_7d' => DemoRequest::query()->where('created_at', '>=', now()->subDays(7))->count(),
            'pending_payments' => PlatformPayment::query()->where('status', PlatformPayment::STATUS_PENDING)->count(),
            'currency' => PlatformSettings::defaultCurrency(),
        ];
    }
}
