<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('pharmacy_name');
            $table->string('owner_name');
            $table->string('phone', 32);
            $table->string('whatsapp', 32)->nullable();
            $table->string('email')->nullable();
            $table->unsignedSmallInteger('branch_count')->default(1);
            $table->text('address')->nullable();
            $table->string('source', 32)->default('other');
            $table->foreignId('interested_plan_id')->nullable()->constrained('subscription_plans')->nullOnDelete();
            $table->string('status', 32)->default('new');
            $table->text('notes')->nullable();
            $table->timestamp('follow_up_at')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('converted_tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'follow_up_at']);
            $table->index(['assigned_to', 'status']);
            $table->index('phone');
        });

        Schema::create('lead_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 32);
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32)->nullable();
            $table->text('body')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['lead_id', 'created_at']);
        });

        Schema::create('demo_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->string('pharmacy_name');
            $table->string('owner_name');
            $table->string('mobile', 32);
            $table->unsignedSmallInteger('branch_count')->default(1);
            $table->timestamp('preferred_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->text('message')->nullable();
            $table->text('meeting_notes')->nullable();
            $table->string('status', 32)->default('pending');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
            $table->index('mobile');
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->foreignId('lead_id')->nullable()->after('reseller_id')->constrained('leads')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lead_id');
        });

        Schema::dropIfExists('demo_requests');
        Schema::dropIfExists('lead_activities');
        Schema::dropIfExists('leads');
    }
};
