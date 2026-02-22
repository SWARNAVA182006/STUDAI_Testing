<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the ai_prompts table for managing versioned AI prompts.
     * This enables prompt A/B testing, version control, and easy tuning
     * without code deployments.
     */
    public function up(): void
    {
        Schema::create('ai_prompts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('category')->default('general')->index();
            $table->integer('version')->default(1);
            $table->text('system_prompt')->nullable();
            $table->text('template');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->json('variables')->nullable();
            $table->json('metadata')->nullable();
            $table->string('model_hint')->nullable();
            $table->integer('max_tokens')->nullable();
            $table->decimal('temperature', 3, 2)->nullable();
            $table->unsignedBigInteger('usage_count')->default(0);
            $table->decimal('avg_latency_ms', 10, 2)->nullable();
            $table->decimal('success_rate', 5, 2)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->unique(['name', 'version'], 'ai_prompts_name_version_unique');
            $table->index(['category', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_prompts');
    }
};
