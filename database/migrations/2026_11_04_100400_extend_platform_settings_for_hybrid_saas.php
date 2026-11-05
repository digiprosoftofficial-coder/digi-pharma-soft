<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->boolean('self_service_enabled')->default(true)->after('default_country_code');
            $table->boolean('self_service_requires_approval')->default(false)->after('self_service_enabled');
            $table->text('payment_instructions')->nullable()->after('self_service_requires_approval');
            $table->string('bkash_merchant_number', 32)->nullable()->after('payment_instructions');
            $table->string('nagad_merchant_number', 32)->nullable()->after('bkash_merchant_number');
            $table->text('bank_transfer_details')->nullable()->after('nagad_merchant_number');
            $table->string('whatsapp_provider', 64)->nullable()->after('bank_transfer_details');
            $table->text('whatsapp_api_key')->nullable()->after('whatsapp_provider');
            $table->json('notification_preferences')->nullable()->after('whatsapp_api_key');
        });
    }

    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->dropColumn([
                'self_service_enabled',
                'self_service_requires_approval',
                'payment_instructions',
                'bkash_merchant_number',
                'nagad_merchant_number',
                'bank_transfer_details',
                'whatsapp_provider',
                'whatsapp_api_key',
                'notification_preferences',
            ]);
        });
    }
};
