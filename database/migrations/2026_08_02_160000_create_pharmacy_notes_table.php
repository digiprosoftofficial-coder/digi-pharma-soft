<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 120)->nullable();
            $table->text('body');
            $table->string('type', 32)->default('general');
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_done')->default(false);
            $table->timestamp('done_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'is_done', 'is_pinned']);
            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_notes');
    }
};
