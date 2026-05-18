<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->unsignedSmallInteger('audit_log_retention_days')->default(365)->after('feature_flags');
            $table->unsignedSmallInteger('compliance_export_retention_days')->default(7)->after('audit_log_retention_days');
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->timestamp('deletion_requested_at')->nullable()->after('internal_notes');
            $table->timestamp('data_purged_at')->nullable()->after('deletion_requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['deletion_requested_at', 'data_purged_at']);
        });

        Schema::table('platform_settings', function (Blueprint $table) {
            $table->dropColumn(['audit_log_retention_days', 'compliance_export_retention_days']);
        });
    }
};
