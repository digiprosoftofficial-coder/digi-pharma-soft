<?php

namespace App\Http\Controllers\Tenant\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\FinancialReportService;
use App\Services\Reports\ReportFilter;
use App\Services\Reports\ReportOptionService;
use App\Services\Reports\ReportOutputService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

final class FinancialReportController extends Controller
{
    public function __construct(
        private readonly FinancialReportService $reports,
        private readonly ReportOutputService $output,
        private readonly ReportOptionService $options,
    ) {}

    public function index(Request $request): InertiaResponse
    {
        abort_unless(auth()->user()?->can('reports.finance') || auth()->user()?->can('accounting.view'), 403);
        $filter = ReportFilter::fromRequest($request);

        return Inertia::render('Reports/FinancialReports', [
            'summary' => $this->reports->summary($filter),
            'entries' => $this->reports->entries($filter),
            'paymentBreakdown' => $this->reports->paymentBreakdown($filter),
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
            'financial-reports',
            'Financial Reports',
            $filter,
            $this->reports->summary($filter),
            ['Posted At', 'Account Code', 'Account', 'Type', 'Direction', 'Amount', 'Memo', 'Reference Type', 'Reference ID'],
            $this->reports->exportRows($filter),
        );
    }

    private function canOutput(Request $request): bool
    {
        if ($request->query('format') === 'print') {
            return auth()->user()?->can('reports.finance') || auth()->user()?->can('accounting.view');
        }

        return auth()->user()?->can('reports.export') ?? false;
    }
}
