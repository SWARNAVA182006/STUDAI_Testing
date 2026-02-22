<?php

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
        // S.C.O.U.T. Assessments table - Main assessment record
        Schema::create('scout_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->onDelete('cascade');
            $table->foreignId('job_id')->constrained('jobs')->onDelete('cascade');
            $table->enum('type', ['comprehensive', 'technical', 'behavioral', 'case_study'])->default('comprehensive');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'expired'])->default('pending');
            
            $table->integer('total_questions')->default(5);
            $table->integer('questions_answered')->default(0);
            $table->string('current_difficulty')->default('medium'); // easy, medium, hard, expert
            $table->boolean('adaptive_enabled')->default(true);
            
            $table->integer('time_limit_minutes')->default(60);
            $table->decimal('final_score', 5, 2)->nullable(); // 0-100 weighted score
            $table->json('performance_summary')->nullable(); // Final metrics
            $table->json('metadata')->nullable(); // Context data
            
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('application_id');
            $table->index('job_id');
            $table->index('status');
            $table->index('type');
        });

        // Scout Assessment Questions table - Questions in each assessment
        Schema::create('scout_assessment_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('scout_assessments')->onDelete('cascade');
            
            $table->integer('question_number'); // Order in assessment
            $table->text('question_text');
            $table->enum('question_type', ['multiple_choice', 'coding', 'essay', 'case_study']);
            $table->enum('difficulty', ['easy', 'medium', 'hard', 'expert']);
            $table->string('category'); // technical, behavioral, problem_solving, system_design, leadership
            
            $table->text('expected_answer')->nullable(); // For auto-grading
            $table->json('evaluation_criteria'); // Criteria for evaluation
            $table->integer('time_limit_seconds')->default(300); // 5 minutes default
            $table->integer('points')->default(50); // Points for this question
            
            // Multiple choice specific
            $table->json('options')->nullable(); // ["A. Option 1", "B. Option 2"]
            
            // Coding specific
            $table->text('code_template')->nullable(); // Starter code
            
            // Case study specific
            $table->text('context')->nullable(); // Background scenario
            
            $table->timestamps();

            // Indexes
            $table->index('assessment_id');
            $table->index(['assessment_id', 'question_number']);
            $table->index('difficulty');
            $table->index('category');
        });

        // Scout Assessment Responses table - Candidate answers
        Schema::create('scout_assessment_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('scout_assessments')->onDelete('cascade');
            $table->foreignId('question_id')->constrained('scout_assessment_questions')->onDelete('cascade');
            
            $table->text('answer')->nullable(); // Text answer or selected option
            $table->longText('code_submission')->nullable(); // For coding questions
            
            $table->boolean('is_correct')->default(false);
            $table->decimal('score', 5, 2)->default(0); // Points earned
            $table->decimal('max_score', 5, 2); // Max points possible
            
            $table->integer('time_taken_seconds')->nullable(); // Response time
            $table->integer('confidence_level')->nullable(); // 1-5 scale, self-reported
            
            $table->text('evaluation_feedback')->nullable(); // AI feedback
            $table->json('evaluation_details')->nullable(); // Detailed evaluation data
            
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('assessment_id');
            $table->index('question_id');
            $table->unique(['assessment_id', 'question_id']); // One response per question
        });

        // Scout Assessment Analytics table - Aggregate performance tracking
        Schema::create('scout_assessment_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('job_id')->nullable()->constrained('jobs')->onDelete('cascade');
            
            $table->string('question_category'); // technical, behavioral, etc.
            $table->string('difficulty'); // easy, medium, hard, expert
            
            // Aggregate metrics
            $table->integer('total_attempts')->default(0);
            $table->integer('total_correct')->default(0);
            $table->decimal('average_score', 5, 2)->default(0);
            $table->decimal('average_time', 8, 2)->default(0); // Seconds
            
            // Distribution
            $table->json('score_distribution')->nullable(); // Histogram data
            $table->json('time_distribution')->nullable();
            
            $table->date('analytics_date'); // Daily snapshots
            $table->timestamps();

            // Indexes
            $table->index('company_id');
            $table->index('job_id');
            $table->index(['question_category', 'difficulty']);
            $table->unique(['company_id', 'job_id', 'question_category', 'difficulty', 'analytics_date'], 'scout_assessment_analytics_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scout_assessment_analytics');
        Schema::dropIfExists('scout_assessment_responses');
        Schema::dropIfExists('scout_assessment_questions');
        Schema::dropIfExists('scout_assessments');
    }
};
