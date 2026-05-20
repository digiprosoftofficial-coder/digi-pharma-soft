<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('generic_name')->nullable()->after('name');
            $table->decimal('wholesale_price', 14, 4)->nullable()->after('sale_price');
            $table->decimal('vat_percent', 8, 4)->nullable()->after('wholesale_price');
            $table->text('short_description')->nullable()->after('vat_percent');
            $table->string('image_path')->nullable()->after('short_description');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'generic_name',
                'wholesale_price',
                'vat_percent',
                'short_description',
                'image_path',
            ]);
        });
    }
};
