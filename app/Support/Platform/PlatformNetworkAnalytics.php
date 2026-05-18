<?php

namespace App\Support\Platform;

use App\Domain\Catalog\Models\Product;
use App\Domain\Purchasing\Models\Purchase;
use App\Domain\Sales\Models\Sale;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Support\Carbon;

final class PlatformNetworkAnalytics
{
    /**
     * Aggregated network metrics (no customer or line-item PII).
     *
     * @return array<string, mixed>
     */
    public static function snapshot(): array
    {
        $now = now();
        $monthStart = $now->copy()->startOfMonth();
        $thirtyDaysAgo = $now->copy()->subDays(30);

        $salesBase = Sale::query()->withoutGlobalScopes();

        $revenueAllTime = (float) (clone $salesBase)->sum('total');
        $revenueThisMonth = (float) (clone $salesBase)
            ->where('sold_at', '>=', $monthStart)
            ->sum('total');
        $salesThisMonth = (int) (clone $salesBase)
            ->where('sold_at', '>=', $monthStart)
            ->count();

        $activeSellingTenants = (int) (clone $salesBase)
            ->where('sold_at', '>=', $thirtyDaysAgo)
            ->distinct()
            ->count('tenant_id');

        $onboardedTenants = Tenant::query()->count();

        $moduleAdoption = self::moduleAdoption($onboardedTenants);

        $topTenants = self::topTenantsByRevenue($thirtyDaysAgo, 5);

        return [
            'revenue_all_time' => round($revenueAllTime, 2),
            'revenue_this_month' => round($revenueThisMonth, 2),
            'sales_count_this_month' => $salesThisMonth,
            'active_selling_tenants' => $activeSellingTenants,
            'onboarded_tenants' => $onboardedTenants,
            'module_adoption' => $moduleAdoption,
            'top_tenants_30d' => $topTenants,
        ];
    }

    /**
     * @return list<array{tenant_id: int, name: string, revenue: float, sales_count: int}>
     */
    private static function topTenantsByRevenue(Carbon $since, int $limit): array
    {
        $rows = Sale::query()
            ->withoutGlobalScopes()
            ->where('sold_at', '>=', $since)
            ->select('tenant_id')
            ->selectRaw('SUM(total) as revenue')
            ->selectRaw('COUNT(*) as sales_count')
            ->groupBy('tenant_id')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $names = Tenant::query()
            ->whereIn('id', $rows->pluck('tenant_id'))
            ->pluck('name', 'id');

        return $rows->map(fn ($row) => [
            'tenant_id' => (int) $row->tenant_id,
            'name' => (string) ($names[$row->tenant_id] ?? 'Unknown'),
            'revenue' => round((float) $row->revenue, 2),
            'sales_count' => (int) $row->sales_count,
        ])->all();
    }

    /**
     * @return array{products: array{count: int, percent: float}, sales: array{count: int, percent: float}, purchases: array{count: int, percent: float}}
     */
    private static function moduleAdoption(int $onboardedTenants): array
    {
        $denominator = max($onboardedTenants, 1);

        $withProducts = (int) Product::query()
            ->withoutGlobalScopes()
            ->distinct()
            ->count('tenant_id');

        $withSales = (int) Sale::query()
            ->withoutGlobalScopes()
            ->distinct()
            ->count('tenant_id');

        $withPurchases = (int) Purchase::query()
            ->withoutGlobalScopes()
            ->distinct()
            ->count('tenant_id');

        return [
            'products' => self::adoptionSlice($withProducts, $denominator),
            'sales' => self::adoptionSlice($withSales, $denominator),
            'purchases' => self::adoptionSlice($withPurchases, $denominator),
        ];
    }

    /**
     * @return array{count: int, percent: float}
     */
    private static function adoptionSlice(int $count, int $denominator): array
    {
        return [
            'count' => $count,
            'percent' => round(($count / $denominator) * 100, 1),
        ];
    }
}
