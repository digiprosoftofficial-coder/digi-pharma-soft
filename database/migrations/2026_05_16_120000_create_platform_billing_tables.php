<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('billing_status', 32)->default('trialing')->after('subscription_ends_at');
            $table->timestamp('payment_failed_at')->nullable()->after('billing_status');
            $table->timestamp('grace_period_ends_at')->nullable()->after('payment_failed_at');
            $table->string('stripe_customer_id')->nullable()->after('grace_period_ends_at');
        });

        Schema::create('platform_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('invoice_no');
            $table->unsignedInteger('amount_cents');
            $table->string('currency', 3)->default('BDT');
            $table->string('status', 32)->default('open');
            $table->string('provider', 32)->default('manual');
            $table->string('provider_reference')->nullable();
            $table->timestamp('period_start')->nullable();
            $table->timestamp('period_end')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'invoice_no']);
            $table->index(['tenant_id', 'status']);
            $table->index(['status', 'due_at']);
        });

        Schema::table('platform_settings', function (Blueprint $table) {
            $table->unsignedSmallInteger('billing_grace_days')->default(7)->after('compliance_export_retention_days');
            $table->boolean('auto_suspend_on_payment_failure')->default(true)->after('billing_grace_days');
            $table->string('default_currency', 3)->default('BDT')->after('auto_suspend_on_payment_failure');
        });
    }

    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->dropColumn(['billing_grace_days', 'auto_suspend_on_payment_failure', 'default_currency']);
        });

        Schema::dropIfExists('platform_invoices');

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'billing_status',
                'payment_failed_at',
                'grace_period_ends_at',
                'stripe_customer_id',
            ]);
        });
    }
};
