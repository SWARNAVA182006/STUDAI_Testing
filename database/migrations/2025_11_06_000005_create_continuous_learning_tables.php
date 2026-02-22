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
        // Hire Performance Tracking Table
        Schema::create('hire_performance_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('job_id')->constrained('jobs')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // The hired candidate
            
            // Performance Metrics
            $table->date('hire_date');
            $table->string('review_period')->default('probation'); // probation, 6_month, annual, 18_month, 24_month
            $table->decimal('performance_rating', 3, 2); // Overall rating (1.00-5.00)
            $table->decimal('technical_skills_rating', 3, 2)->nullable();
            $table->decimal('soft_skills_rating', 3, 2)->nullable();
            $table->decimal('cultural_fit_rating', 3, 2)->nullable();
            $table->decimal('productivity_rating', 3, 2)->nullable();
            $table->decimal('team_collaboration_rating', 3, 2)->nullable();
            $table->decimal('leadership_rating', 3, 2)->nullable();
            
            // Status & Progress
            $table->string('retention_status')->default('active'); // active, promoted, transferred, resigned_early, terminated, resigned_planned
            $table->integer('promotion_count')->default(0);
            
            // Qualitative Feedback
            $table->text('manager_feedback')->nullable();
            $table->text('peer_feedback')->nullable();
            $table->json('achievements')->nullable(); // List of achievements
            $table->json('challenges')->nullable(); // Challenges faced
            
            // Learning Analytics
            $table->json('actual_vs_predicted_performance')->nullable(); // Compare with S.C.O.U.T. predictions
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['company_id', 'performance_rating']);
            $table->index(['company_id', 'retention_status']);
            $table->index(['company_id', 'review_period']);
            $table->index('hire_date');
            $table->unique(['application_id', 'review_period']); // One record per review period per hire
        });

        // Assessment Refinements Table
        Schema::create('assessment_refinements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            
            // Refinement Details
            $table->string('refinement_type'); // comprehensive, technical_focus, cultural_focus, role_specific
            $table->integer('data_points_analyzed'); // Number of hires analyzed
            $table->date('time_period_start');
            $table->date('time_period_end');
            
            // Criteria Evolution
            $table->json('previous_criteria'); // Old assessment criteria
            $table->json('refined_criteria'); // New refined criteria
            $table->json('previous_weights'); // Old factor weights
            $table->json('refined_weights'); // New optimized weights
            
            // Analysis Results
            $table->json('correlation_analysis'); // Factor correlations with success
            $table->decimal('performance_improvement_estimate', 5, 2); // Estimated % improvement
            $table->decimal('confidence_score', 5, 2); // Confidence in refinement (0-100)
            $table->text('ai_insights')->nullable(); // AI-generated insights
            
            // Application Status
            $table->timestamp('applied_at')->nullable(); // When refinement was applied
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['company_id', 'created_at']);
            $table->index('confidence_score');
        });

        // Hiring Decision Overrides Table
        Schema::create('hiring_decision_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('job_id')->constrained('jobs')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Hiring manager
            
            // Decision Details
            $table->string('scout_recommendation'); // STRONG HIRE, RECOMMEND, CONSIDER, CAUTION, NOT RECOMMENDED
            $table->string('manager_decision'); // Same options
            $table->string('override_type'); // hire_despite_caution, reject_despite_recommendation, agreement, other
            
            // Override Reasoning
            $table->text('override_reason')->nullable(); // Why manager overrode
            $table->json('override_factors')->nullable(); // Specific factors considered
            $table->string('confidence_level')->nullable(); // high, medium, low
            
            // Outcome Tracking
            $table->string('outcome')->default('pending'); // pending, validated, refuted, neutral
            $table->json('outcome_notes')->nullable(); // How the override turned out
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['company_id', 'override_type']);
            $table->index(['company_id', 'outcome']);
            $table->index('created_at');
        });

        // Success Pattern Analytics Table
        Schema::create('success_pattern_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('job_id')->nullable()->constrained('jobs')->onDelete('set null'); // Null = company-wide
            
            // Analysis Period
            $table->date('analysis_start_date');
            $table->date('analysis_end_date');
            $table->integer('successful_hires_count');
            $table->integer('unsuccessful_hires_count');
            
            // Pattern Data
            $table->json('success_characteristics'); // Common traits of successful hires
            $table->json('failure_characteristics'); // Common traits of unsuccessful hires
            $table->json('key_differentiators'); // What separates success from failure
            $table->json('correlation_strengths'); // Factor correlation scores
            
            // Insights
            $table->text('ai_insights')->nullable(); // AI-generated pattern insights
            $table->json('recommended_adjustments')->nullable(); // Suggested criteria changes
            $table->decimal('pattern_confidence', 5, 2); // Confidence in patterns (0-100)
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['company_id', 'analysis_end_date']);
            $table->index(['company_id', 'job_id']);
        });

        // Talent Need Predictions Table
        Schema::create('talent_need_predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            
            // Prediction Parameters
            $table->integer('prediction_horizon_months'); // 3, 6, 12, 24
            $table->date('prediction_generated_date');
            $table->date('prediction_target_date');
            
            // Predictions
            $table->json('predicted_roles'); // Roles likely to be hired
            $table->integer('predicted_headcount'); // Total predicted hires
            $table->json('predicted_skills_demand'); // Skills that will be in demand
            $table->json('predicted_department_growth')->nullable(); // Growth by department
            
            // Prediction Basis
            $table->json('growth_factors'); // What's driving the predictions
            $table->json('industry_trends'); // Industry trend analysis
            $table->json('seasonality_factors')->nullable(); // Seasonal hiring patterns
            $table->json('prediction_basis'); // Data used for prediction
            
            // Confidence & Insights
            $table->decimal('confidence_score', 5, 2); // Prediction confidence (0-100)
            $table->integer('data_points_used'); // Amount of historical data
            $table->json('recommendations')->nullable(); // Proactive hiring recommendations
            $table->text('ai_analysis')->nullable(); // AI-generated analysis
            
            // Validation (filled in after prediction period)
            $table->integer('actual_headcount')->nullable(); // Actual hires during period
            $table->decimal('prediction_accuracy', 5, 2)->nullable(); // How accurate was prediction
            $table->json('accuracy_analysis')->nullable(); // What was accurate/inaccurate
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['company_id', 'prediction_target_date']);
            $table->index('confidence_score');
        });

        // Learning Insights Cache Table (for quick dashboard access)
        Schema::create('learning_insights_cache', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            
            // Cache Data
            $table->string('insight_type'); // performance_trends, override_patterns, dna_evolution, talent_predictions
            $table->json('insight_data'); // Cached insight data
            $table->timestamp('generated_at');
            $table->timestamp('expires_at');
            
            // Metadata
            $table->integer('data_freshness_score')->default(100); // How fresh is the data (0-100)
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['company_id', 'insight_type']);
            $table->index('expires_at');
            $table->unique(['company_id', 'insight_type']); // One cache entry per type per company
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learning_insights_cache');
        Schema::dropIfExists('talent_need_predictions');
        Schema::dropIfExists('success_pattern_analytics');
        Schema::dropIfExists('hiring_decision_overrides');
        Schema::dropIfExists('assessment_refinements');
        Schema::dropIfExists('hire_performance_tracking');
    }
};
