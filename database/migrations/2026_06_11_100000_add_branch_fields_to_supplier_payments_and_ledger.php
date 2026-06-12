<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_payments', function (Blueprint $table) {
            $table->foreignId('paying_branch_id')->nullable()->after('tenant_id')->constrained('branches')->cascadeOnDelete();
        });

        foreach (DB::table('purchase_payments')->select('purchase_payments.id', 'purchases.branch_id')->join('purchases', 'purchases.id', '=', 'purchase_payments.purchase_id')->get() as $row) {
            if ($row->branch_id) {
                DB::table('purchase_payments')->where('id', $row->id)->update(['paying_branch_id' => $row->branch_id]);
            }
        }

        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('tenant_id')->constrained('branches')->cascadeOnDelete();
        });

        $defaultBranches = DB::table('branches')->where('is_default', true)->pluck('id', 'tenant_id');
        foreach (DB::table('ledger_entries')->select('id', 'tenant_id')->get() as $row) {
            $branchId = $defaultBranches[$row->tenant_id] ?? null;
            if ($branchId) {
                DB::table('ledger_entries')->where('id', $row->id)->update(['branch_id' => $branchId]);
            }
        }

        Schema::table('purchases', function (Blueprint $table) {
            $table->index(['tenant_id', 'supplier_id', 'branch_id', 'status'], 'purchases_tenant_supplier_branch_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex('purchases_tenant_supplier_branch_status_idx');
        });

        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
        });

        Schema::table('purchase_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('paying_branch_id');
        });
    }
};
