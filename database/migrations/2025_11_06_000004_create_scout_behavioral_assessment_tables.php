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
        // Main behavioral assessments table
        Schema::create('scout_behavioral_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->onDelete('cascade');
            $table->foreignId('job_id')->constrained('jobs')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            
            $table->enum('status', ['pending', 'in_progress', 'completed', 'expired'])->default('pending');
            $table->integer('scenario_count')->default(6);
            
            // Scoring fields
            $table->decimal('cultural_fit_score', 5, 2)->nullable();
            $table->decimal('emotional_intelligence_score', 5, 2)->nullable();
            $table->decimal('leadership_score', 5, 2)->nullable();
            $table->decimal('communication_score', 5, 2)->nullable();
            $table->decimal('approach_quality_score', 5, 2)->nullable();
            $table->decimal('reasoning_quality_score', 5, 2)->nullable();
            $table->decimal('overall_score', 5, 2)->nullable();
            
            // Assessment configuration
            $table->string('assessment_type')->default('comprehensive'); // comprehensive, cultural_fit_focus, leadership_focus
            $table->json('focus_areas')->nullable(); // ['cultural_fit', 'emotional_intelligence', 'leadership']
            $table->json('company_culture_context')->nullable(); // Company culture analysis
            
            // Results
            $table->string('thriving_likelihood')->nullable(); // Highly Likely, Likely, May Thrive, Uncertain, Likely to Struggle
            $table->string('recommendation')->nullable(); // STRONG HIRE, RECOMMEND, CONSIDER, CAUTION, NOT RECOMMENDED
            $table->json('final_insights')->nullable(); // Comprehensive AI insights
            
            // Metadata
            $table->json('metadata')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['application_id', 'job_id']);
            $table->index('company_id');
            $table->index('status');
            $table->index('cultural_fit_score');
            $table->index('overall_score');
            $table->index('completed_at');
        });

        // Situational scenarios table
        Schema::create('scout_situational_scenarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('behavioral_assessment_id')
                ->constrained('scout_behavioral_assessments')
                ->onDelete('cascade');
            
            $table->integer('scenario_number'); // Order in assessment
            $table->string('title'); // Brief scenario name
            $table->text('context'); // Background information
            $table->text('situation'); // The specific challenge
            $table->string('category'); // team_conflict, decision_making, leadership, etc.
            $table->enum('difficulty_level', ['easy', 'medium', 'hard', 'expert'])->default('medium');
            
            // Valid approaches (JSON array of approach objects)
            $table->json('valid_approaches'); // Array of 4-5 valid response options
            $table->integer('preferred_approach')->default(0); // Index of culturally preferred approach
            
            // Evaluation criteria
            $table->json('cultural_alignment_weights')->nullable(); // How to score different approaches
            $table->json('evaluates_dimensions')->nullable(); // Which EI/leadership dimensions tested
            
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['behavioral_assessment_id', 'scenario_number'], 'situational_scenarios_assessment_idx');
            $table->index('category');
            $table->index('difficulty_level');
        });

        // Scenario responses table
        Schema::create('scout_scenario_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('behavioral_assessment_id')
                ->constrained('scout_behavioral_assessments')
                ->onDelete('cascade');
            $table->foreignId('situational_scenario_id')
                ->constrained('scout_situational_scenarios')
                ->onDelete('cascade');
            
            // Response data
            $table->integer('selected_approach')->nullable(); // Which approach was chosen (index)
            $table->text('reasoning'); // Candidate's explanation
            $table->integer('time_taken')->default(0); // Seconds spent on scenario
            
            // Evaluation scores
            $table->decimal('cultural_alignment_score', 5, 2)->default(0); // 0-100
            $table->decimal('approach_quality_score', 5, 2)->default(0); // 0-100
            $table->decimal('reasoning_quality_score', 5, 2)->default(0); // 0-100
            $table->decimal('overall_score', 5, 2)->default(0); // 0-100
            
            // Analysis results
            $table->json('ei_dimensions_demonstrated')->nullable(); // ['empathy', 'self_regulation', ...]
            $table->json('leadership_competencies_shown')->nullable(); // ['decision_making', 'conflict_resolution', ...]
            $table->json('communication_patterns_detected')->nullable(); // ['clarity', 'diplomacy', ...]
            $table->json('strengths_identified')->nullable(); // What was done well
            $table->json('areas_for_improvement')->nullable(); // Constructive feedback
            
            $table->text('ai_feedback')->nullable(); // AI-generated comprehensive feedback
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('behavioral_assessment_id');
            $table->index('situational_scenario_id');
            $table->index('cultural_alignment_score');
            $table->index('overall_score');
            
            // Unique constraint - one response per scenario per assessment
            $table->unique(['behavioral_assessment_id', 'situational_scenario_id'], 'unique_scenario_response');
        });

        // Behavioral analytics table (aggregate company/job insights)
        Schema::create('scout_behavioral_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('job_id')->nullable()->constrained('jobs')->onDelete('cascade');
            
            $table->string('period_type')->default('all_time'); // all_time, monthly, quarterly
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            
            // Aggregate statistics
            $table->integer('total_assessments')->default(0);
            $table->integer('completed_assessments')->default(0);
            
            // Average scores
            $table->decimal('avg_cultural_fit_score', 5, 2)->nullable();
            $table->decimal('avg_emotional_intelligence_score', 5, 2)->nullable();
            $table->decimal('avg_leadership_score', 5, 2)->nullable();
            $table->decimal('avg_communication_score', 5, 2)->nullable();
            $table->decimal('avg_overall_score', 5, 2)->nullable();
            
            // Distribution of recommendations
            $table->integer('strong_hire_count')->default(0);
            $table->integer('recommend_count')->default(0);
            $table->integer('consider_count')->default(0);
            $table->integer('caution_count')->default(0);
            $table->integer('not_recommended_count')->default(0);
            
            // Common patterns
            $table->json('top_ei_dimensions')->nullable(); // Most demonstrated EI dimensions
            $table->json('top_leadership_competencies')->nullable(); // Most shown leadership skills
            $table->json('common_communication_patterns')->nullable(); // Frequent communication styles
            $table->json('scenario_category_performance')->nullable(); // Performance by scenario type
            
            // Cultural insights
            $table->json('high_fit_characteristics')->nullable(); // What high-fit candidates have in common
            $table->json('low_fit_characteristics')->nullable(); // What low-fit candidates have in common
            
            $table->json('metadata')->nullable();
            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('company_id');
            $table->index('job_id');
            $table->index(['company_id', 'period_type']);
            $table->index(['company_id', 'job_id', 'period_type']);
            $table->index('last_calculated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scout_behavioral_analytics');
        Schema::dropIfExists('scout_scenario_responses');
        Schema::dropIfExists('scout_situational_scenarios');
        Schema::dropIfExists('scout_behavioral_assessments');
    }
};
