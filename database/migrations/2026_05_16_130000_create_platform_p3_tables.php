<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->string('default_locale', 8)->default('en')->after('default_currency');
            $table->string('default_timezone', 64)->default('Asia/Dhaka')->after('default_locale');
            $table->string('default_country_code', 2)->default('BD')->after('default_timezone');
        });

        Schema::create('resellers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 64)->nullable();
            $table->decimal('commission_percent', 5, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->foreignId('reseller_id')->nullable()->after('slug')->constrained()->nullOnDelete();
        });

        Schema::create('catalog_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });

        Schema::create('catalog_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_template_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('sku');
            $table->string('barcode')->nullable();
            $table->string('unit', 32)->default('pcs');
            $table->string('generic_name')->nullable();
            $table->string('manufacturer_name')->nullable();
            $table->decimal('purchase_price', 14, 4)->default(0);
            $table->decimal('sale_price', 14, 4)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['catalog_template_id', 'sku']);
        });

        Schema::create('platform_announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->string('severity', 16)->default('info');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_announcements');
        Schema::dropIfExists('catalog_template_items');
        Schema::dropIfExists('catalog_templates');

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reseller_id');
        });

        Schema::dropIfExists('resellers');

        Schema::table('platform_settings', function (Blueprint $table) {
            $table->dropColumn(['default_locale', 'default_timezone', 'default_country_code']);
        });
    }
};
