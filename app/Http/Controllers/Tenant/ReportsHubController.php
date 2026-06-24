<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\Reports\DueReportService;
use App\Services\Reports\InventoryReportService;
use App\Services\Reports\PurchaseReportService;
use App\Services\Reports\ReportFilter;
use App\Services\Reports\SalesReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ReportsHubController extends Controller
{
    public function __construct(
        private readonly SalesReportService $sales,
        private readonly PurchaseReportService $purchases,
        private readonly InventoryReportService $inventory,
        private readonly DueReportService $dues,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless(auth()->user()?->can('reports.view'), 403);

        $filter = ReportFilter::fromRequest($request);
        $sales = $this->sales->summary($filter);
        $purchases = $this->purchases->summary($filter);
        $inventory = $this->inventory->summary($filter);
        $dues = $this->dues->summary($filter);

        return Inertia::render('Reports/Hub', [
            'snapshot' => [
                'range' => [
                    'dateFrom' => $filter->dateFrom->toDateString(),
                    'dateTo' => $filter->dateTo->toDateString(),
                    'branchLabel' => $filter->branchLabel,
                ],
                'sales' => $sales,
                'purchases' => $purchases,
                'inventory' => $inventory,
                'dues' => $dues,
            ],
        ]);
    }
}
