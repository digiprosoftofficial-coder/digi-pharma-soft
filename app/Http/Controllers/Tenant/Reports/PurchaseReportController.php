<?php

namespace App\Http\Controllers\Tenant\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\PurchaseReportService;
use App\Services\Reports\ReportFilter;
use App\Services\Reports\ReportOutputService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

final class PurchaseReportController extends Controller
{
    public function __construct(
        private readonly PurchaseReportService $reports,
        private readonly ReportOutputService $output,
    ) {}

    public function summary(Request $request): InertiaResponse
    {
        abort_unless(auth()->user()?->can('reports.view'), 403);

        $filter = ReportFilter::fromRequest($request);

        return Inertia::render('Reports/PurchaseSummary', [
            'summary' => $this->reports->summary($filter),
            'purchases' => $this->reports->purchases($filter),
            'topSuppliers' => $this->reports->topSuppliers($filter),
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
            'purchase-summary',
            'Purchase Summary',
            $filter,
            $this->reports->summary($filter),
            ['Invoice', 'Date', 'Branch', 'Supplier', 'Total', 'Paid', 'Due', 'Status'],
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
