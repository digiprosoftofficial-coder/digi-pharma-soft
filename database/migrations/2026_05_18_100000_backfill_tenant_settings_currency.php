<?php

use App\Domain\Tenant\Models\Tenant;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Tenant::query()->cursor()->each(function (Tenant $tenant): void {
            $settings = $tenant->settings ?? [];
            $code = strtoupper((string) ($settings['currency'] ?? ''));

            if (strlen($code) !== 3) {
                $settings['currency'] = 'BDT';
                $tenant->settings = $settings;
                $tenant->save();
            }
        });
    }

    public function down(): void
    {
        // Non-destructive backfill; no rollback needed.
    }
};
