<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storage_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 32)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'is_active', 'sort_order']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('storage_location_id')
                ->nullable()
                ->after('manufacturer_id')
                ->constrained('storage_locations')
                ->nullOnDelete();
        });

        Schema::table('product_batches', function (Blueprint $table) {
            $table->foreignId('storage_location_id')
                ->nullable()
                ->after('product_id')
                ->constrained('storage_locations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('product_batches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('storage_location_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('storage_location_id');
        });

        Schema::dropIfExists('storage_locations');
    }
};
