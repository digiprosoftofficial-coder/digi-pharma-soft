<?php

namespace App\Services\Reports;

use App\Exports\Reports\ArrayReportExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ReportOutputService
{
    /**
     * @param  array<string, mixed>  $summary
     * @param  array<int, string>  $headings
     * @param  array<int, array<int, mixed>>  $rows
     */
    public function respond(
        Request $request,
        string $slug,
        string $title,
        ReportFilter $filter,
        array $summary,
        array $headings,
        array $rows,
    ): Response|BinaryFileResponse {
        $format = $request->query('format', 'csv');
        $fileBase = $slug.'-'.$filter->dateFrom->toDateString().'-to-'.$filter->dateTo->toDateString();

        return match ($format) {
            'print' => response()->view('reports.print', $this->viewData($title, $filter, $summary, $headings, $rows)),
            'pdf' => Pdf::loadView('reports.pdf', $this->viewData($title, $filter, $summary, $headings, $rows))
                ->setPaper('a4', 'landscape')
                ->download($fileBase.'.pdf'),
            'xlsx', 'excel' => Excel::download(new ArrayReportExport($headings, $rows), $fileBase.'.xlsx'),
            default => $this->csv($fileBase.'.csv', $headings, $rows),
        };
    }

    /**
     * @param  array<string, mixed>  $summary
     * @param  array<int, string>  $headings
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function viewData(string $title, ReportFilter $filter, array $summary, array $headings, array $rows): array
    {
        return [
            'title' => $title,
            'tenant' => \tenant(),
            'branchLabel' => $filter->branchLabel,
            'dateFrom' => $filter->dateFrom->toDateString(),
            'dateTo' => $filter->dateTo->toDateString(),
            'generatedBy' => auth()->user()?->name,
            'generatedAt' => now()->format('Y-m-d H:i:s'),
            'summary' => $summary,
            'headings' => $headings,
            'rows' => $rows,
        ];
    }

    /**
     * @param  array<int, string>  $headings
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function csv(string $fileName, array $headings, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headings, $rows): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headings);

            foreach ($rows as $row) {
                fputcsv($out, $row);
            }

            fclose($out);
        }, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
