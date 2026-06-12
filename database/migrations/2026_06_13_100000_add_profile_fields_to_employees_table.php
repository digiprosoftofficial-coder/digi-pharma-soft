<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('name')->default('')->after('user_id');
            $table->string('phone', 64)->nullable()->after('name');
            $table->string('designation')->nullable()->after('phone');
            $table->boolean('is_active')->default(true)->after('designation');
        });

        foreach (DB::table('employees')->select('employees.id', 'employees.employee_code', 'employees.user_id', 'users.name as user_name')->leftJoin('users', 'users.id', '=', 'employees.user_id')->get() as $row) {
            $name = $row->user_name ?: $row->employee_code;
            DB::table('employees')->where('id', $row->id)->update(['name' => $name]);
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->unique(['tenant_id', 'user_id'], 'employees_tenant_user_unique');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique('employees_tenant_user_unique');
            $table->dropColumn(['name', 'phone', 'designation', 'is_active']);
        });
    }
};
