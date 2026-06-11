<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Sales\Models\Sale;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ReportController extends Controller
{
    public function summary(Request $request): Response
    {
        abort_unless(auth()->user()?->can('reports.view'), 403);

        $tenantId = tenant_id();
        $from = $request->date('date_from') ?? now()->subDays(30)->startOfDay();
        $to = $request->date('date_to') ?? now()->endOfDay();

        $salesInRange = Sale::query()
            ->whereBetween('sold_at', [$from, $to])
            ->orderByDesc('sold_at')
            ->paginate(20)
            ->withQueryString();

        $salesTotal = (float) Sale::query()->whereBetween('sold_at', [$from, $to])->sum('total');

        $topProductsQuery = DB::table('sale_lines')
            ->select('sale_lines.product_id', DB::raw('SUM(sale_lines.quantity) as qty'), DB::raw('SUM(sale_lines.line_total) as revenue'))
            ->join('sales', 'sales.id', '=', 'sale_lines.sale_id')
            ->where('sale_lines.tenant_id', $tenantId)
            ->whereBetween('sale_lines.created_at', [$from, $to]);

        if (\branch_id()) {
            $topProductsQuery->where('sales.branch_id', \branch_id());
        }

        $topProducts = $topProductsQuery
            ->groupBy('product_id')
            ->orderByDesc('qty')
            ->limit(10)
            ->get();

        return Inertia::render('Reports/Summary', [
            'dateFrom' => $from->toDateString(),
            'dateTo' => $to->toDateString(),
            'salesTotal' => $salesTotal,
            'salesInRange' => $salesInRange,
            'topProducts' => $topProducts,
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        abort_unless(auth()->user()?->can('reports.view'), 403);

        $from = $request->date('date_from') ?? now()->subDays(30)->startOfDay();
        $to = $request->date('date_to') ?? now()->endOfDay();

        $fileName = 'sales-'.$from->format('Y-m-d').'_to_'.$to->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($from, $to) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['invoice_no', 'sold_at', 'total', 'paid', 'due', 'status']);
            Sale::query()
                ->whereBetween('sold_at', [$from, $to])
                ->orderBy('sold_at')
                ->cursor()
                ->each(function (Sale $sale) use ($out) {
                    fputcsv($out, [
                        $sale->invoice_no,
                        $sale->sold_at->toDateTimeString(),
                        $sale->total,
                        $sale->paid,
                        $sale->due,
                        $sale->status,
                    ]);
                });
            fclose($out);
        }, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
