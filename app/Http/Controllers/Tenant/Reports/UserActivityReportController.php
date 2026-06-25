<?php

namespace App\Http\Controllers\Tenant\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\ReportFilter;
use App\Services\Reports\ReportOptionService;
use App\Services\Reports\ReportOutputService;
use App\Services\Reports\UserActivityReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

final class UserActivityReportController extends Controller
{
    public function __construct(
        private readonly UserActivityReportService $reports,
        private readonly ReportOutputService $output,
        private readonly ReportOptionService $options,
    ) {}

    public function index(Request $request): InertiaResponse
    {
        abort_unless(auth()->user()?->can('reports.activity') || auth()->user()?->can('team.users.view'), 403);
        $filter = ReportFilter::fromRequest($request);

        return Inertia::render('Reports/UserActivityReports', [
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
            'user-activity',
            'User Activity Reports',
            $filter,
            $this->reports->summary($filter),
            ['Date', 'Event', 'Description', 'User', 'Email', 'Subject Type', 'Subject ID', 'IP'],
            $this->reports->exportRows($filter),
        );
    }

    private function canOutput(Request $request): bool
    {
        if ($request->query('format') === 'print') {
            return auth()->user()?->can('reports.activity') || auth()->user()?->can('team.users.view');
        }

        return auth()->user()?->can('reports.export') ?? false;
    }
}
