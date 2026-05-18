<?php

namespace App\Support\Platform;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class PlatformSystemHealth
{
    /**
     * @return array<string, mixed>
     */
    public static function snapshot(bool $includeFailedJobDetails = false): array
    {
        $failedJobs = self::failedJobsCount();
        $pendingJobs = self::pendingJobsCount();

        $data = [
            'queue_connection' => (string) config('queue.default'),
            'failed_jobs' => $failedJobs,
            'pending_jobs' => $pendingJobs,
            'status' => self::overallStatus($failedJobs, $pendingJobs),
            'app_env' => (string) config('app.env'),
            'app_debug' => (bool) config('app.debug'),
            'latest_migration' => self::latestMigrationBatch(),
        ];

        if ($includeFailedJobDetails) {
            $data['recent_failed_jobs'] = self::recentFailedJobs(15);
        }

        return $data;
    }

    private static function failedJobsCount(): int
    {
        if (! Schema::hasTable('failed_jobs')) {
            return 0;
        }

        return (int) DB::table('failed_jobs')->count();
    }

    private static function pendingJobsCount(): int
    {
        if (! Schema::hasTable('jobs')) {
            return 0;
        }

        return (int) DB::table('jobs')
            ->where('available_at', '<=', now()->timestamp)
            ->whereNull('reserved_at')
            ->count();
    }

    /**
     * @return array{batch: int|null, ran_at: string|null}
     */
    private static function latestMigrationBatch(): array
    {
        if (! Schema::hasTable('migrations')) {
            return ['batch' => null, 'ran_at' => null];
        }

        $latest = DB::table('migrations')->orderByDesc('id')->first();

        if ($latest === null) {
            return ['batch' => null, 'ran_at' => null];
        }

        return [
            'batch' => (int) $latest->batch,
            'ran_at' => null,
        ];
    }

    /**
     * @return list<array{id: int, uuid: string, queue: string, connection: string, failed_at: string|null, exception_summary: string}>
     */
    private static function recentFailedJobs(int $limit): array
    {
        if (! Schema::hasTable('failed_jobs')) {
            return [];
        }

        return DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->limit($limit)
            ->get(['id', 'uuid', 'queue', 'connection', 'exception', 'failed_at'])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'uuid' => (string) $row->uuid,
                'queue' => (string) $row->queue,
                'connection' => (string) $row->connection,
                'failed_at' => $row->failed_at ? (string) $row->failed_at : null,
                'exception_summary' => Str::limit((string) $row->exception, 200),
            ])
            ->all();
    }

    private static function overallStatus(int $failedJobs, int $pendingJobs): string
    {
        if ($failedJobs > 0) {
            return 'degraded';
        }

        if ($pendingJobs > 500) {
            return 'warning';
        }

        return 'healthy';
    }
}
