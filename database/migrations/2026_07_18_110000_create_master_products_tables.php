<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('generic_name')->nullable();
            $table->string('strength')->nullable();
            $table->string('manufacturer_name')->nullable();
            $table->string('product_type', 64)->default('other');
            $table->string('drug_class')->nullable();
            $table->string('base_unit', 32)->default('strip');
            $table->decimal('pieces_per_strip', 14, 4)->nullable();
            $table->decimal('strips_per_box', 14, 4)->nullable();
            $table->decimal('boxes_per_carton', 14, 4)->nullable();
            $table->string('sku', 64)->nullable();
            $table->string('barcode')->nullable();
            $table->decimal('mrp', 14, 4)->default(0);
            $table->decimal('default_purchase_price', 14, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('sku');
            $table->index('name');
            $table->index('generic_name');
            $table->index('barcode');
            $table->index(['is_active', 'name']);
        });

        Schema::create('master_product_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_product_id')->constrained()->cascadeOnDelete();
            $table->string('sell_unit', 32);
            $table->decimal('conversion_factor', 14, 4)->default(1);
            $table->decimal('purchase_price', 14, 4)->default(0);
            $table->decimal('sale_price', 14, 4)->default(0);
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['master_product_id', 'sell_unit'], 'master_product_units_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_product_units');
        Schema::dropIfExists('master_products');
    }
};
