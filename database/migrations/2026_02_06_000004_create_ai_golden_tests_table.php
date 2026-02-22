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
        Schema::create('ai_golden_tests', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('category');
            $table->string('prompt_name')->nullable();
            $table->text('input');
            $table->json('input_variables')->nullable();
            $table->text('expected_output');
            $table->json('expected_json_schema')->nullable();
            $table->json('required_keywords')->nullable();
            $table->json('forbidden_keywords')->nullable();
            $table->float('min_similarity_score')->default(0.7);
            $table->string('evaluation_type')->default('similarity');
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->integer('run_count')->default(0);
            $table->integer('pass_count')->default(0);
            $table->integer('fail_count')->default(0);
            $table->float('avg_similarity_score')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->string('last_run_status')->nullable();
            $table->text('last_run_output')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['category', 'is_active']);
            $table->index('prompt_name');
        });

        Schema::create('ai_golden_test_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('golden_test_id')->constrained('ai_golden_tests')->cascadeOnDelete();
            $table->text('actual_output');
            $table->float('similarity_score')->nullable();
            $table->boolean('passed');
            $table->json('evaluation_details')->nullable();
            $table->float('latency_ms');
            $table->string('model_used')->nullable();
            $table->json('errors')->nullable();
            $table->timestamps();

            $table->index(['golden_test_id', 'created_at']);
            $table->index('passed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_golden_test_runs');
        Schema::dropIfExists('ai_golden_tests');
    }
};
