<?php

namespace App\Http\Controllers\Tenant\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\InventoryReportService;
use App\Services\Reports\ReportFilter;
use App\Services\Reports\ReportOutputService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

final class InventoryReportController extends Controller
{
    public function __construct(
        private readonly InventoryReportService $reports,
        private readonly ReportOutputService $output,
    ) {}

    public function health(Request $request): InertiaResponse
    {
        abort_unless(auth()->user()?->can('reports.view'), 403);

        $filter = ReportFilter::fromRequest($request);

        return Inertia::render('Reports/InventoryHealth', [
            'summary' => $this->reports->summary($filter),
            'batches' => $this->reports->batches($filter),
            'lowStock' => $this->reports->lowStock($filter),
            'expiryRisk' => $this->reports->expiryRisk($filter),
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
            'inventory-health',
            'Inventory Health',
            $filter,
            $this->reports->summary($filter),
            ['Product', 'SKU', 'Batch', 'Branch', 'Location', 'Quantity', 'Unit Cost', 'Stock Value', 'Expiry', 'Min Stock'],
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
