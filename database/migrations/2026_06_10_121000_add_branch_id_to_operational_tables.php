<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $defaultBranches = DB::table('branches')->where('is_default', true)->pluck('id', 'tenant_id');

        $this->addBranchToTable('product_batches', $defaultBranches);
        $this->addBranchToTable('storage_locations', $defaultBranches);
        $this->addBranchToTable('stock_movements', $defaultBranches);
        $this->addBranchToTable('purchases', $defaultBranches);
        $this->addBranchToTable('sales', $defaultBranches);
        $this->addBranchToTable('purchase_returns', $defaultBranches);
        $this->addBranchToTable('sale_returns', $defaultBranches);

        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->foreignId('from_branch_id')->nullable()->after('tenant_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('to_branch_id')->nullable()->after('from_branch_id')->constrained('branches')->cascadeOnDelete();
        });

        foreach (DB::table('stock_transfers')->select('id', 'tenant_id')->get() as $row) {
            $branchId = $defaultBranches[$row->tenant_id] ?? null;
            if ($branchId) {
                DB::table('stock_transfers')->where('id', $row->id)->update([
                    'from_branch_id' => $branchId,
                    'to_branch_id' => $branchId,
                ]);
            }
        }

        Schema::table('product_batches', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'product_id', 'batch_no']);
            $table->unique(['tenant_id', 'branch_id', 'product_id', 'batch_no'], 'product_batches_tenant_branch_product_batch_unique');
        });

        Schema::table('storage_locations', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'code']);
            $table->unique(['tenant_id', 'branch_id', 'code'], 'storage_locations_tenant_branch_code_unique');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'invoice_no']);
            $table->unique(['tenant_id', 'branch_id', 'invoice_no'], 'purchases_tenant_branch_invoice_unique');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'invoice_no']);
            $table->unique(['tenant_id', 'branch_id', 'invoice_no'], 'sales_tenant_branch_invoice_unique');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique('sales_tenant_branch_invoice_unique');
            $table->unique(['tenant_id', 'invoice_no']);
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropUnique('purchases_tenant_branch_invoice_unique');
            $table->unique(['tenant_id', 'invoice_no']);
        });

        Schema::table('storage_locations', function (Blueprint $table) {
            $table->dropUnique('storage_locations_tenant_branch_code_unique');
            $table->unique(['tenant_id', 'code']);
        });

        Schema::table('product_batches', function (Blueprint $table) {
            $table->dropUnique('product_batches_tenant_branch_product_batch_unique');
            $table->unique(['tenant_id', 'product_id', 'batch_no']);
        });

        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('to_branch_id');
            $table->dropConstrainedForeignId('from_branch_id');
        });

        foreach (['sale_returns', 'purchase_returns', 'sales', 'purchases', 'stock_movements', 'storage_locations', 'product_batches'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropConstrainedForeignId('branch_id');
            });
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int>  $defaultBranches
     */
    private function addBranchToTable(string $tableName, $defaultBranches): void
    {
        Schema::table($tableName, function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('tenant_id')->constrained('branches')->cascadeOnDelete();
        });

        foreach (DB::table($tableName)->select('id', 'tenant_id')->get() as $row) {
            $branchId = $defaultBranches[$row->tenant_id] ?? null;
            if ($branchId) {
                DB::table($tableName)->where('id', $row->id)->update(['branch_id' => $branchId]);
            }
        }
    }
};
