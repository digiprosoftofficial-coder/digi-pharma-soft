<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_product_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('suggested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
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
            $table->string('status', 32)->default('pending');
            $table->foreignId('master_product_id')->nullable()->constrained('master_products')->nullOnDelete();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();

            $table->unique('product_id');
            $table->index(['status', 'created_at']);
            $table->index('barcode');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_product_suggestions');
    }
};
