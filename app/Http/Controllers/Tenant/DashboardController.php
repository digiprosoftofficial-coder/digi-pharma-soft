<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Catalog\Models\ProductBatch;
use App\Domain\Sales\Models\Customer;
use App\Domain\Sales\Models\Sale;
use App\Http\Controllers\Controller;
use App\Support\Tenant\TenantImpersonation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

final class DashboardController extends Controller
{
    public function index(): Response|RedirectResponse
    {
        $user = auth()->user();
        if ($user && $user->shouldUsePlatformDashboard() && ! app(TenantImpersonation::class)->isActive()) {
            return redirect()->route('platform.dashboard');
        }

        $tenantId = tenant_id();
        $revenueToday = (float) Sale::query()->whereDate('sold_at', today())->sum('total');
        $revenueYesterday = (float) Sale::query()->whereDate('sold_at', today()->subDay())->sum('total');
        $pendingOrders = Sale::query()->where('due', '>', 0)->count();
        $lowStockCount = ProductBatch::query()
            ->join('products', 'products.id', '=', 'product_batches.product_id')
            ->whereRaw('product_batches.quantity_on_hand < products.min_stock')
            ->where('products.is_active', true)
            ->count();
        $customerCount = Customer::query()->count();

        $chartDays = collect(range(6, 0))->map(function (int $daysAgo) use ($tenantId) {
            $d = today()->subDays($daysAgo);

            return [
                'label' => $d->format('D'),
                'total' => (float) Sale::query()->whereDate('sold_at', $d)->sum('total'),
            ];
        });

        $criticalStock = ProductBatch::query()
            ->join('products', 'products.id', '=', 'product_batches.product_id')
            ->whereRaw('product_batches.quantity_on_hand < products.min_stock')
            ->where('products.is_active', true)
            ->with('product')
            ->orderBy('product_batches.quantity_on_hand')
            ->limit(8)
            ->get(['product_batches.*']);

        $activities = Activity::query()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->limit(15)
            ->get(['description', 'event', 'created_at'])
            ->map(fn (Activity $a) => [
                'description' => $a->description,
                'event' => $a->event,
                'created_at' => $a->created_at?->toIso8601String(),
            ]);

        return Inertia::render('Tenant/Dashboard', [
            'headline' => __('Pharmacy overview'),
            'kpis' => [
                'revenueToday' => $revenueToday,
                'revenueYesterday' => $revenueYesterday,
                'pendingOrders' => $pendingOrders,
                'lowStockCount' => $lowStockCount,
                'customerCount' => $customerCount,
            ],
            'chartDays' => $chartDays,
            'criticalStock' => $criticalStock,
            'activities' => $activities,
        ]);
    }
}
