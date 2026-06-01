<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('default_markup_percent', 8, 2)->nullable()->after('sale_price');
        });

        Schema::table('product_batches', function (Blueprint $table) {
            $table->decimal('markup_percent', 8, 2)->nullable()->after('purchase_unit_cost');
        });

        Schema::table('sale_lines', function (Blueprint $table) {
            $table->decimal('unit_cost_at_sale', 14, 4)->nullable()->after('unit_price');
        });
    }

    public function down(): void
    {
        Schema::table('sale_lines', function (Blueprint $table) {
            $table->dropColumn('unit_cost_at_sale');
        });

        Schema::table('product_batches', function (Blueprint $table) {
            $table->dropColumn('markup_percent');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('default_markup_percent');
        });
    }
};
