<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('default_trial_days')->default(14);
            $table->string('support_email')->nullable();
            $table->string('support_phone', 64)->nullable();
            $table->string('sms_provider', 64)->nullable();
            $table->text('sms_api_key')->nullable();
            $table->json('feature_flags')->nullable();
            $table->timestamps();
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->text('internal_notes')->nullable()->after('settings');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_login_at')->nullable()->after('email_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('last_login_at');
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('internal_notes');
        });

        Schema::dropIfExists('platform_settings');
    }
};
