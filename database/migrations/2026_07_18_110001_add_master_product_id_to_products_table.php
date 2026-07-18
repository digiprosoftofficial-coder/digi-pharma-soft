<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('master_product_id')
                ->nullable()
                ->after('tenant_id')
                ->constrained('master_products')
                ->nullOnDelete();
            $table->index(['tenant_id', 'master_product_id']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'master_product_id']);
            $table->dropConstrainedForeignId('master_product_id');
        });
    }
};
