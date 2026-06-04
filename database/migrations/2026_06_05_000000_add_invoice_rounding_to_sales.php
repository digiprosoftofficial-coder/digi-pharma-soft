<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('rounded_total', 14, 4)->nullable()->after('total');
            $table->decimal('round_adjustment', 14, 4)->default(0)->after('rounded_total');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['rounded_total', 'round_adjustment']);
        });
    }
};
