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
        $today = today()->toDateString();
        $nearExpiryDate = today()->addDays(30)->toDateString();

        $revenueToday = (float) Sale::query()
            ->withoutGlobalScope('branch')
            ->where('status', 'posted')
            ->whereDate('sold_at', $today)
            ->sum(DB::raw('COALESCE(rounded_total, total)'));
        $revenueYesterday = (float) Sale::query()
            ->withoutGlobalScope('branch')
            ->where('status', 'posted')
            ->whereDate('sold_at', today()->subDay())
            ->sum(DB::raw('COALESCE(rounded_total, total)'));
        $profitToday = (float) DB::table('sale_lines')
            ->join('sales', 'sales.id', '=', 'sale_lines.sale_id')
            ->where('sale_lines.tenant_id', $tenantId)
            ->where('sales.tenant_id', $tenantId)
            ->where('sales.status', 'posted')
            ->whereDate('sales.sold_at', $today)
            ->sum(DB::raw('sale_lines.line_total - (sale_lines.quantity * COALESCE(sale_lines.unit_cost_at_sale, 0))'));
        $purchaseToday = (float) DB::table('purchases')
            ->where('tenant_id', $tenantId)
            ->where('status', 'posted')
            ->whereDate('purchased_at', $today)
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

        $chartDays = collect(range(6, 0))->map(function (int $daysAgo) {
            $d = today()->subDays($daysAgo);

            return [
                'label' => $d->format('D'),
                'total' => (float) Sale::query()
                    ->withoutGlobalScope('branch')
                    ->where('status', 'posted')
                    ->whereDate('sold_at', $d)
                    ->sum(DB::raw('COALESCE(rounded_total, total)')),
            ];
        });

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
            ->whereDate('sales.sold_at', $today)
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

        $salesTodayByBranch = DB::table('sales')
            ->select('branch_id', DB::raw('SUM(COALESCE(rounded_total, total)) as sales_today'))
            ->where('tenant_id', $tenantId)
            ->where('status', 'posted')
            ->whereDate('sold_at', $today)
            ->groupBy('branch_id');

        $purchasesTodayByBranch = DB::table('purchases')
            ->select('branch_id', DB::raw('SUM(total) as purchases_today'))
            ->where('tenant_id', $tenantId)
            ->where('status', 'posted')
            ->whereDate('purchased_at', $today)
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
            ->leftJoinSub($salesTodayByBranch, 'sales_today_by_branch', 'sales_today_by_branch.branch_id', '=', 'branches.id')
            ->leftJoinSub($purchasesTodayByBranch, 'purchases_today_by_branch', 'purchases_today_by_branch.branch_id', '=', 'branches.id')
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
            'kpis' => [
                'revenueToday' => $revenueToday,
                'revenueYesterday' => $revenueYesterday,
                'profitToday' => $profitToday,
                'purchaseToday' => $purchaseToday,
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
}
