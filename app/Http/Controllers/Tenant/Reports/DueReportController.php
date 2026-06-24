<?php

namespace App\Http\Controllers\Tenant\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\DueReportService;
use App\Services\Reports\ReportFilter;
use App\Services\Reports\ReportOutputService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

final class DueReportController extends Controller
{
    public function __construct(
        private readonly DueReportService $reports,
        private readonly ReportOutputService $output,
    ) {}

    public function index(Request $request): InertiaResponse
    {
        abort_unless(auth()->user()?->can('reports.view'), 403);

        $filter = ReportFilter::fromRequest($request);

        return Inertia::render('Reports/Dues', [
            'summary' => $this->reports->summary($filter),
            'customers' => $this->reports->customerRows($filter, 50),
            'suppliers' => $this->reports->supplierRows($filter, 50),
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
            'dues',
            'Customer And Supplier Dues',
            $filter,
            $this->reports->summary($filter),
            ['Type', 'Name', 'Phone', 'Scope', 'Due'],
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
