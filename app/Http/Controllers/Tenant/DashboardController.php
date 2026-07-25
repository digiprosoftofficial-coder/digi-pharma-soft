<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Catalog\Models\ProductBatch;
use App\Domain\Sales\Models\Customer;
use App\Domain\Sales\Models\Sale;
use App\Http\Controllers\Controller;
use App\Support\Dashboard\DashboardDateRange;
use App\Support\Tenant\TenantImpersonation;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

final class DashboardController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        $user = auth()->user();
        if ($user && $user->shouldUsePlatformDashboard() && ! app(TenantImpersonation::class)->isActive()) {
            return redirect()->route('platform.dashboard');
        }

        $range = DashboardDateRange::fromRequest($request);
        $tenantId = tenant_id();
        $today = today()->toDateString();
        $nearExpiryDate = today()->addDays(30)->toDateString();
        $from = $range->from;
        $to = $range->to;

        $revenue = (float) Sale::query()
            ->withoutGlobalScope('branch')
            ->where('status', 'posted')
            ->whereBetween('sold_at', [$from, $to])
            ->sum(DB::raw('COALESCE(rounded_total, total)'));

        $revenueYesterday = (float) Sale::query()
            ->withoutGlobalScope('branch')
            ->where('status', 'posted')
            ->whereDate('sold_at', today()->subDay())
            ->sum(DB::raw('COALESCE(rounded_total, total)'));

        $profit = (float) DB::table('sale_lines')
            ->join('sales', 'sales.id', '=', 'sale_lines.sale_id')
            ->where('sale_lines.tenant_id', $tenantId)
            ->where('sales.tenant_id', $tenantId)
            ->where('sales.status', 'posted')
            ->whereBetween('sales.sold_at', [$from, $to])
            ->sum(DB::raw('sale_lines.line_total - (sale_lines.quantity * COALESCE(sale_lines.unit_cost_at_sale, 0))'));

        $purchase = (float) DB::table('purchases')
            ->where('tenant_id', $tenantId)
            ->where('status', 'posted')
            ->whereBetween('purchased_at', [$from->toDateString(), $to->toDateString()])
            ->sum('total');

        $stockValue = (float) DB::table('product_batches')
            ->where('tenant_id', $tenantId)
            ->sum(DB::raw('quantity_on_hand * purchase_unit_cost'));
        $customerDue = (float) DB::table('customers')
            ->where('tenant_id', $tenantId)
            ->sum('balance_due');
        $supplierDue = max(0, (float) DB::table('purchases')
            ->where('tenant_id', $tenantId)
            ->where('status', 'posted')
            ->sum('due') - (float) DB::table('purchase_returns')
            ->where('tenant_id', $tenantId)
            ->where('status', 'posted')
            ->sum('total_credit'));
        $expiredProducts = (int) DB::table('product_batches')
            ->where('tenant_id', $tenantId)
            ->where('quantity_on_hand', '>', 0)
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<', $today)
            ->count();
        $nearExpiryProducts = (int) DB::table('product_batches')
            ->where('tenant_id', $tenantId)
            ->where('quantity_on_hand', '>', 0)
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '>=', $today)
            ->whereDate('expiry_date', '<=', $nearExpiryDate)
            ->count();
        $pendingOrders = Sale::query()
            ->withoutGlobalScope('branch')
            ->where('status', 'posted')
            ->where('due', '>', 0)
            ->count();
        $lowStockCount = ProductBatch::query()
            ->withoutGlobalScope('branch')
            ->join('products', 'products.id', '=', 'product_batches.product_id')
            ->whereRaw('product_batches.quantity_on_hand < products.min_stock')
            ->where('products.is_active', true)
            ->count();
        $customerCount = Customer::query()->count();

        $chartDays = $this->chartDays();

        $criticalStock = ProductBatch::query()
            ->withoutGlobalScope('branch')
            ->join('products', 'products.id', '=', 'product_batches.product_id')
            ->whereRaw('product_batches.quantity_on_hand < products.min_stock')
            ->where('products.is_active', true)
            ->with('product')
            ->orderBy('product_batches.quantity_on_hand')
            ->limit(8)
            ->get(['product_batches.*']);

        $topMedicines = DB::table('sale_lines')
            ->join('sales', 'sales.id', '=', 'sale_lines.sale_id')
            ->leftJoin('products', 'products.id', '=', 'sale_lines.product_id')
            ->where('sale_lines.tenant_id', $tenantId)
            ->where('sales.tenant_id', $tenantId)
            ->where('sales.status', 'posted')
            ->whereBetween('sales.sold_at', [$from, $to])
            ->select(
                'sale_lines.product_id',
                DB::raw("COALESCE(products.name, CONCAT('Product #', sale_lines.product_id)) as product_name"),
                DB::raw('SUM(sale_lines.quantity_base) as quantity'),
                DB::raw('SUM(sale_lines.line_total) as revenue'),
            )
            ->groupBy('sale_lines.product_id', 'products.name')
            ->orderByDesc('quantity')
            ->limit(8)
            ->get();

        $salesInRangeByBranch = DB::table('sales')
            ->select('branch_id', DB::raw('SUM(COALESCE(rounded_total, total)) as sales_today'))
            ->where('tenant_id', $tenantId)
            ->where('status', 'posted')
            ->whereBetween('sold_at', [$from, $to])
            ->groupBy('branch_id');

        $purchasesInRangeByBranch = DB::table('purchases')
            ->select('branch_id', DB::raw('SUM(total) as purchases_today'))
            ->where('tenant_id', $tenantId)
            ->where('status', 'posted')
            ->whereBetween('purchased_at', [$from->toDateString(), $to->toDateString()])
            ->groupBy('branch_id');

        $salesDueByBranch = DB::table('sales')
            ->select('branch_id', DB::raw('SUM(due) as sales_due'))
            ->where('tenant_id', $tenantId)
            ->where('status', 'posted')
            ->groupBy('branch_id');

        $stockValueByBranch = DB::table('product_batches')
            ->select('branch_id', DB::raw('SUM(quantity_on_hand * purchase_unit_cost) as stock_value'))
            ->where('tenant_id', $tenantId)
            ->groupBy('branch_id');

        $branchPerformance = DB::table('branches')
            ->leftJoinSub($salesInRangeByBranch, 'sales_today_by_branch', 'sales_today_by_branch.branch_id', '=', 'branches.id')
            ->leftJoinSub($purchasesInRangeByBranch, 'purchases_today_by_branch', 'purchases_today_by_branch.branch_id', '=', 'branches.id')
            ->leftJoinSub($salesDueByBranch, 'sales_due_by_branch', 'sales_due_by_branch.branch_id', '=', 'branches.id')
            ->leftJoinSub($stockValueByBranch, 'stock_value_by_branch', 'stock_value_by_branch.branch_id', '=', 'branches.id')
            ->where('branches.tenant_id', $tenantId)
            ->where('branches.is_active', true)
            ->select(
                'branches.id',
                'branches.name',
                'branches.code',
                DB::raw('COALESCE(sales_today_by_branch.sales_today, 0) as sales_today'),
                DB::raw('COALESCE(purchases_today_by_branch.purchases_today, 0) as purchases_today'),
                DB::raw('COALESCE(sales_due_by_branch.sales_due, 0) as sales_due'),
                DB::raw('COALESCE(stock_value_by_branch.stock_value, 0) as stock_value'),
            )
            ->orderByDesc('sales_today')
            ->limit(8)
            ->get();

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
            'headline' => __('dashboard.executive_dashboard'),
            'dateRange' => $range->toArray(),
            'rangeOptions' => collect(DashboardDateRange::PRESETS)->map(fn (string $key) => [
                'value' => $key,
                'label' => __('dashboard.range_'.$key),
            ])->values()->all(),
            'kpis' => [
                'revenue' => $revenue,
                'revenueToday' => $revenue,
                'revenueYesterday' => $revenueYesterday,
                'profit' => $profit,
                'profitToday' => $profit,
                'purchase' => $purchase,
                'purchaseToday' => $purchase,
                'stockValue' => $stockValue,
                'customerDue' => $customerDue,
                'supplierDue' => $supplierDue,
                'expiredProducts' => $expiredProducts,
                'nearExpiryProducts' => $nearExpiryProducts,
                'pendingOrders' => $pendingOrders,
                'lowStockCount' => $lowStockCount,
                'customerCount' => $customerCount,
            ],
            'chartDays' => $chartDays,
            'criticalStock' => $criticalStock,
            'topMedicines' => $topMedicines,
            'branchPerformance' => $branchPerformance,
            'activities' => $activities,
        ]);
    }

    /**
     * This chart is intentionally independent from the dashboard date filter.
     *
     * @return list<array{label: string, date: string, total: float}>
     */
    private function chartDays(): array
    {
        $chartTo = CarbonImmutable::today();
        $chartFrom = $chartTo->subDays(6);

        $totals = Sale::query()
            ->withoutGlobalScope('branch')
            ->where('status', 'posted')
            ->whereBetween('sold_at', [$chartFrom->startOfDay(), $chartTo->endOfDay()])
            ->selectRaw('DATE(sold_at) as day, SUM(COALESCE(rounded_total, total)) as total')
            ->groupByRaw('DATE(sold_at)')
            ->get()
            ->mapWithKeys(fn ($row) => [(string) $row->day => (float) $row->total]);

        return collect(range(0, 6))->map(function (int $offset) use ($chartFrom, $totals) {
            $day = $chartFrom->addDays($offset);

            return [
                'label' => $day->format('D'),
                'date' => $day->format('d M'),
                'total' => (float) ($totals[$day->toDateString()] ?? 0),
            ];
        })->all();
    }
}
