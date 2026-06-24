<?php

namespace App\Http\Controllers\Tenant\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\ReportFilter;
use App\Services\Reports\ReportOutputService;
use App\Services\Reports\SalesReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

final class SalesReportController extends Controller
{
    public function __construct(
        private readonly SalesReportService $reports,
        private readonly ReportOutputService $output,
    ) {}

    public function summary(Request $request): InertiaResponse
    {
        abort_unless(auth()->user()?->can('reports.view'), 403);

        $filter = ReportFilter::fromRequest($request);

        return Inertia::render('Reports/SalesSummary', [
            'summary' => $this->reports->summary($filter),
            'sales' => $this->reports->sales($filter),
            'topProducts' => $this->reports->topProducts($filter),
            'filters' => $filter->raw,
            'branchLabel' => $filter->branchLabel,
            'canViewAllBranches' => $filter->canViewAllBranches,
            'branches' => ReportFilter::branchOptions(),
        ]);
    }

    public function export(Request $request): Response
    {
        abort_unless($this->canOutput($request), 403);

        $filter = ReportFilter::fromRequest($request);

        return $this->output->respond(
            $request,
            'sales-summary',
            'Sales Summary',
            $filter,
            $this->reports->summary($filter),
            ['Invoice', 'Date', 'Branch', 'Customer', 'Total', 'Paid', 'Due', 'Status'],
            $this->reports->exportRows($filter),
        );
    }

    private function canOutput(Request $request): bool
    {
        if ($request->query('format') === 'print') {
            return auth()->user()?->can('reports.view') ?? false;
        }

        return auth()->user()?->can('reports.export') ?? false;
    }
}
