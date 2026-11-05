<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->unsignedInteger('monthly_price_cents')->default(0)->after('price_cents');
            $table->unsignedInteger('yearly_price_cents')->default(0)->after('monthly_price_cents');
            $table->boolean('is_active')->default(true)->after('trial_days');
            $table->unsignedSmallInteger('sort_order')->default(100)->after('is_active');
        });

        DB::table('subscription_plans')->orderBy('id')->each(function ($plan): void {
            DB::table('subscription_plans')->where('id', $plan->id)->update([
                'monthly_price_cents' => (int) $plan->price_cents,
                'yearly_price_cents' => (int) $plan->price_cents * 10,
            ]);
        });

        Schema::table('tenant_subscriptions', function (Blueprint $table) {
            $table->string('billing_interval', 16)->default('yearly')->after('status');
            $table->timestamp('cancelled_at')->nullable()->after('billing_interval');
            $table->json('meta')->nullable()->after('cancelled_at');
        });

        Schema::table('platform_invoices', function (Blueprint $table) {
            $table->string('billing_interval', 16)->nullable()->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('platform_invoices', function (Blueprint $table) {
            $table->dropColumn('billing_interval');
        });

        Schema::table('tenant_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['billing_interval', 'cancelled_at', 'meta']);
        });

        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn(['monthly_price_cents', 'yearly_price_cents', 'is_active', 'sort_order']);
        });
    }
};
