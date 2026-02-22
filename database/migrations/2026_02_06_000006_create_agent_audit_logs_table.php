<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('agent_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_configuration_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('action_type');
            $table->string('status')->default('success');
            $table->foreignId('target_job_id')->nullable()->constrained('job_listings')->nullOnDelete();
            $table->foreignId('auto_application_id')->nullable()->constrained()->nullOnDelete();
            $table->json('input_data')->nullable();
            $table->json('output_data')->nullable();
            $table->text('error_message')->nullable();
            $table->float('duration_ms')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'action', 'created_at']);
            $table->index(['action_type', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_audit_logs');
    }
};
