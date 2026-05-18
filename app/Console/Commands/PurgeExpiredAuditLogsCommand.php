<?php

namespace App\Console\Commands;

use App\Support\Platform\PlatformSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Spatie\Activitylog\Models\Activity;

final class PurgeExpiredAuditLogsCommand extends Command
{
    protected $signature = 'platform:purge-compliance-retention';

    protected $description = 'Purge audit log entries and stale compliance export files per platform retention settings';

    public function handle(): int
    {
        $settings = PlatformSettings::get();
        $auditDays = max(30, (int) $settings['audit_log_retention_days']);
        $exportDays = max(1, (int) $settings['compliance_export_retention_days']);

        $auditCutoff = now()->subDays($auditDays);
        $deletedAudit = Activity::query()
            ->where('created_at', '<', $auditCutoff)
            ->delete();

        $exportCutoff = now()->subDays($exportDays)->getTimestamp();
        $exportDir = storage_path('app/compliance-exports');
        $deletedExports = 0;

        if (is_dir($exportDir)) {
            foreach (File::glob("{$exportDir}/*.zip") ?: [] as $file) {
                if (filemtime($file) < $exportCutoff) {
                    @unlink($file);
                    $deletedExports++;
                }
            }
        }

        $this->info("Purged {$deletedAudit} audit log rows older than {$auditDays} days.");
        $this->info("Removed {$deletedExports} compliance export archive(s) older than {$exportDays} days.");

        return self::SUCCESS;
    }
}
