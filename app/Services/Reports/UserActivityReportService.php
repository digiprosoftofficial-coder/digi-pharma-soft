<?php

namespace App\Services\Reports;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\Activitylog\Models\Activity;

final class UserActivityReportService
{
    public function summary(ReportFilter $filter): array
    {
        $query = $this->query($filter);

        return [
            'events' => (clone $query)->count(),
            'users' => (clone $query)->whereNotNull('causer_id')->distinct('causer_id')->count('causer_id'),
            'logins' => (clone $query)->where('event', 'login')->count(),
            'imports' => (clone $query)->where('event', 'catalog.imported')->count(),
        ];
    }

    public function rows(ReportFilter $filter, int $perPage = 30): LengthAwarePaginator
    {
        return $this->query($filter)
            ->with('causer:id,name,email')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function exportRows(ReportFilter $filter): array
    {
        return $this->query($filter)
            ->with('causer:id,name,email')
            ->orderBy('created_at')
            ->get()
            ->map(fn (Activity $activity) => [
                $activity->created_at?->format('Y-m-d H:i'),
                $activity->event,
                $activity->description,
                $activity->causer?->name,
                $activity->causer?->email,
                $activity->subject_type,
                $activity->subject_id,
                $activity->properties?->get('ip'),
            ])
            ->all();
    }

    private function query(ReportFilter $filter)
    {
        return Activity::query()
            ->where('tenant_id', \tenant_id())
            ->whereBetween('created_at', [$filter->dateFrom, $filter->dateTo])
            ->when($filter->userId, fn ($q) => $q->where('causer_id', $filter->userId))
            ->when($filter->eventType, fn ($q) => $q->where('event', $filter->eventType));
    }
}
