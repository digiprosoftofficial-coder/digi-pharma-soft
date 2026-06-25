<?php

namespace App\Http\Controllers\Tenant\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\BranchReportService;
use App\Services\Reports\ReportFilter;
use App\Services\Reports\ReportOptionService;
use App\Services\Reports\ReportOutputService;
use App\Support\Tenant\TenantFeatures;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

final class BranchReportController extends Controller
{
    public function __construct(
        private readonly BranchReportService $reports,
        private readonly ReportOutputService $output,
        private readonly ReportOptionService $options,
    ) {}

    public function index(Request $request): InertiaResponse
    {
        abort_unless(TenantFeatures::multiBranchEnabled(tenant()), 403);
        abort_unless(auth()->user()?->can('reports.view_all_branches') || auth()->user()?->can('branches.view'), 403);
        $filter = ReportFilter::fromRequest($request);

        return Inertia::render('Reports/BranchReports', [
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
        abort_unless(TenantFeatures::multiBranchEnabled(tenant()), 403);
        abort_unless($this->canOutput($request), 403);
        $filter = ReportFilter::fromRequest($request);

        return $this->output->respond(
            $request,
            'branch-reports',
            'Branch Reports',
            $filter,
            $this->reports->summary($filter),
            ['Branch', 'Code', 'Sales', 'Purchases', 'Sales Due', 'Purchase Due', 'Stock Value', 'Expiry Risk', 'Transfers Out', 'Transfers In'],
            $this->reports->exportRows($filter),
        );
    }

    private function canOutput(Request $request): bool
    {
        if ($request->query('format') === 'print') {
            return auth()->user()?->can('reports.view_all_branches') || auth()->user()?->can('branches.view');
        }

        return auth()->user()?->can('reports.export') ?? false;
    }
}
