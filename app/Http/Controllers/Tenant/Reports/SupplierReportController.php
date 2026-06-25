<?php

namespace App\Http\Controllers\Tenant\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\ReportFilter;
use App\Services\Reports\ReportOptionService;
use App\Services\Reports\ReportOutputService;
use App\Services\Reports\SupplierReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

final class SupplierReportController extends Controller
{
    public function __construct(
        private readonly SupplierReportService $reports,
        private readonly ReportOutputService $output,
        private readonly ReportOptionService $options,
    ) {}

    public function index(Request $request): InertiaResponse
    {
        abort_unless(auth()->user()?->can('reports.view'), 403);
        $filter = ReportFilter::fromRequest($request);

        return Inertia::render('Reports/SupplierReports', [
            'summary' => $this->reports->summary($filter),
            'rows' => $this->reports->rows($filter),
            'filters' => $filter->raw,
            'branchLabel' => $filter->branchLabel,
            'canViewAllBranches' => $filter->canViewAllBranches,
            'options' => $this->options->common(),
        ]);
    }

    public function export(Request $request): Response
    {
        abort_unless($this->canOutput($request), 403);
        $filter = ReportFilter::fromRequest($request);

        return $this->output->respond(
            $request,
            'supplier-reports',
            'Supplier Reports',
            $filter,
            $this->reports->summary($filter),
            ['Supplier', 'Phone', 'Purchases', 'Purchase Total', 'Paid', 'Due', 'Return Credit'],
            $this->reports->exportRows($filter),
        );
    }

    private function canOutput(Request $request): bool
    {
        return $request->query('format') === 'print'
            ? (auth()->user()?->can('reports.view') ?? false)
            : (auth()->user()?->can('reports.export') ?? false);
    }
}
