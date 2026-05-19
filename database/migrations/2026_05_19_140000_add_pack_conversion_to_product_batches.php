<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_batches', function (Blueprint $table) {
            $table->string('pack_sell_unit', 32)->nullable()->after('purchase_unit_cost');
            $table->decimal('pack_conversion_factor', 14, 4)->nullable()->after('pack_sell_unit');
        });
    }

    public function down(): void
    {
        Schema::table('product_batches', function (Blueprint $table) {
            $table->dropColumn(['pack_sell_unit', 'pack_conversion_factor']);
        });
    }
};
