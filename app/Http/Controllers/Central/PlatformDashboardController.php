<?php

namespace App\Http\Controllers\Central;

use App\Domain\Tenant\Models\Tenant;
use App\Http\Controllers\Controller;
use App\Support\Platform\PlatformBillingMetrics;
use App\Support\Platform\PlatformNetworkAnalytics;
use App\Support\Platform\PlatformSystemHealth;
use App\Support\Tenant\TenantPresenter;
use App\Support\Tenant\TenantStatus;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

final class PlatformDashboardController extends Controller
{
    public function index(): Response
    {
        $total = Tenant::query()->count();

        $active = Tenant::query()
            ->where('is_active', true)
            ->whereNull('suspended_at')
            ->where(function ($q) {
                $q->whereNull('subscription_ends_at')
                    ->orWhere('subscription_ends_at', '>=', now());
            })
            ->count();

        $suspended = Tenant::query()->whereNotNull('suspended_at')->count();

        $inactive = Tenant::query()
            ->where('is_active', false)
            ->whereNull('suspended_at')
            ->count();

        $trialCount = Tenant::query()
            ->whereNull('suspended_at')
            ->where('is_active', true)
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>', now())
            ->count();

        $expiredCount = Tenant::query()
            ->whereNull('suspended_at')
            ->whereNotNull('subscription_ends_at')
            ->where('subscription_ends_at', '<', now())
            ->count();

        $expiringSoon = Tenant::query()
            ->with(['activeSubscription.plan'])
            ->withCount('users')
            ->whereNull('suspended_at')
            ->where('is_active', true)
            ->where(function ($q) {
                $threshold = now()->addDays(7);
                $q->whereBetween('trial_ends_at', [now(), $threshold])
                    ->orWhereBetween('subscription_ends_at', [now(), $threshold]);
            })
            ->orderBy('subscription_ends_at')
            ->limit(10)
            ->get();

        $recentTenants = Tenant::query()
            ->withCount('users')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $recentAudit = Activity::query()
            ->with('causer')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get(['id', 'description', 'event', 'created_at', 'causer_type', 'causer_id']);

        return Inertia::render('Platform/Dashboard', [
            'analytics' => PlatformNetworkAnalytics::snapshot(),
            'billing' => PlatformBillingMetrics::snapshot(),
            'health' => PlatformSystemHealth::snapshot(),
            'tenantCount' => $total,
            'activeTenantCount' => $active,
            'suspendedTenantCount' => $suspended,
            'inactiveTenantCount' => $inactive,
            'trialCount' => $trialCount,
            'expiredCount' => $expiredCount,
            'expiringSoon' => TenantPresenter::collection($expiringSoon),
            'recentTenants' => TenantPresenter::collection($recentTenants),
            'recentAudit' => $recentAudit->map(fn (Activity $a) => [
                'description' => $a->description,
                'event' => $a->event,
                'created_at' => $a->created_at?->toIso8601String(),
                'causer_name' => $a->causer?->name,
            ]),
        ]);
    }
}
