<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('status');
        });

        Schema::table('purchase_lines', function (Blueprint $table) {
            $table->date('manufactured_at')->nullable()->after('expiry_date');
            $table->decimal('sale_price', 14, 4)->nullable()->after('unit_cost');
        });

        Schema::table('product_batches', function (Blueprint $table) {
            $table->date('manufactured_at')->nullable()->after('expiry_date');
        });
    }

    public function down(): void
    {
        Schema::table('product_batches', function (Blueprint $table) {
            $table->dropColumn('manufactured_at');
        });

        Schema::table('purchase_lines', function (Blueprint $table) {
            $table->dropColumn(['manufactured_at', 'sale_price']);
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
};
