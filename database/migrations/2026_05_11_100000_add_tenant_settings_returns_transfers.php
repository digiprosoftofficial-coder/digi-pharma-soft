<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->json('settings')->nullable()->after('suspended_at');
        });

        Schema::create('sale_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference_no');
            $table->timestamp('returned_at');
            $table->decimal('total_refund', 14, 4)->default(0);
            $table->text('notes')->nullable();
            $table->string('status', 32)->default('posted');
            $table->timestamps();
            $table->unique(['tenant_id', 'reference_no']);
            $table->index(['tenant_id', 'returned_at']);
        });

        Schema::create('sale_return_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_batch_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 14, 4);
            $table->decimal('unit_price', 14, 4);
            $table->decimal('line_total', 14, 4);
            $table->timestamps();
            $table->index(['tenant_id', 'sale_return_id']);
        });

        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('transfer_no');
            $table->timestamp('transferred_at');
            $table->text('notes')->nullable();
            $table->string('status', 32)->default('posted');
            $table->timestamps();
            $table->unique(['tenant_id', 'transfer_no']);
            $table->index(['tenant_id', 'transferred_at']);
        });

        Schema::create('stock_transfer_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stock_transfer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_batch_id')->constrained('product_batches')->cascadeOnDelete();
            $table->foreignId('to_batch_id')->constrained('product_batches')->cascadeOnDelete();
            $table->decimal('quantity', 14, 4);
            $table->timestamps();
            $table->index(['tenant_id', 'stock_transfer_id']);
        });

        Schema::create('discount_coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('code', 32);
            $table->decimal('percent_off', 5, 2)->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['tenant_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_coupons');
        Schema::dropIfExists('stock_transfer_lines');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('sale_return_lines');
        Schema::dropIfExists('sale_returns');

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('settings');
        });
    }
};
