<?php

use App\Support\Catalog\SeedPlatformProductTypes;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_product_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 64)->unique();
            $table->string('icon_path')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('product_types', function (Blueprint $table) {
            $table->string('icon_path')->nullable()->after('sort_order');
        });

        SeedPlatformProductTypes::run();

        // Backfill tenant copies from platform defaults where icons exist.
        if (Schema::hasTable('tenants')) {
            foreach (\App\Domain\Tenant\Models\Tenant::query()->pluck('id') as $tenantId) {
                \App\Support\Catalog\SeedDefaultProductTypes::syncIconsFromPlatform((int) $tenantId);
            }
        }
    }

    public function down(): void
    {
        Schema::table('product_types', function (Blueprint $table) {
            $table->dropColumn('icon_path');
        });

        Schema::dropIfExists('platform_product_types');
    }
};
