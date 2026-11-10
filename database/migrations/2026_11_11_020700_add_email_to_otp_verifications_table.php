<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('otp_verifications', function (Blueprint $table) {
            $table->string('email', 255)->nullable()->after('phone');
            $table->index(['email', 'purpose', 'expires_at'], 'otp_verifications_email_purpose_expires_index');
        });
    }

    public function down(): void
    {
        Schema::table('otp_verifications', function (Blueprint $table) {
            $table->dropIndex('otp_verifications_email_purpose_expires_index');
            $table->dropColumn('email');
        });
    }
};
