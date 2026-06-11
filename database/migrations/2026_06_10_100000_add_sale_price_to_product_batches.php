<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_batches', function (Blueprint $table) {
            $table->decimal('sale_price', 12, 4)->nullable()->after('purchase_unit_cost');
        });

        $latestLines = DB::table('purchase_lines')
            ->whereNotNull('sale_price')
            ->orderByDesc('id')
            ->get(['product_id', 'batch_no', 'sale_price']);

        $seen = [];
        foreach ($latestLines as $line) {
            $key = $line->product_id.'|'.$line->batch_no;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            DB::table('product_batches')
                ->where('product_id', $line->product_id)
                ->where('batch_no', $line->batch_no)
                ->whereNull('sale_price')
                ->update(['sale_price' => $line->sale_price]);
        }
    }

    public function down(): void
    {
        Schema::table('product_batches', function (Blueprint $table) {
            $table->dropColumn('sale_price');
        });
    }
};
