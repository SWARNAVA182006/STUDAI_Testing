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
        // Table 1: Success Predictions
        Schema::create('scout_success_predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->onDelete('cascade');
            $table->foreignId('job_id')->constrained('job_listings')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('success_probability', 5, 4); // 0.0000 to 1.0000
            $table->decimal('confidence_score', 5, 4);
            $table->enum('success_category', ['very_high', 'high', 'moderate', 'low', 'very_low']);
            $table->json('factor_scores'); // Individual factor contributions
            $table->json('key_strengths')->nullable();
            $table->json('key_concerns')->nullable();
            $table->json('ai_insights')->nullable();
            $table->text('prediction_basis');
            $table->text('recommendation')->nullable();
            $table->timestamp('predicted_at');
            $table->timestamp('actual_outcome_date')->nullable();
            $table->enum('actual_outcome', ['success', 'moderate_success', 'underperformance', 'failure'])->nullable();
            $table->timestamps();

            $table->index(['application_id', 'predicted_at']);
            $table->index(['company_id', 'success_category']);
            $table->index('success_probability');
        });

        // Table 2: Tenure Forecasts
        Schema::create('scout_tenure_forecasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->onDelete('cascade');
            $table->foreignId('job_id')->constrained('job_listings')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->integer('expected_tenure_months');
            $table->integer('tenure_range_min');
            $table->integer('tenure_range_max');
            $table->decimal('confidence_score', 5, 4);
            $table->enum('player_type', ['long_term_player', 'stable_player', 'moderate_risk', 'flight_risk']);
            $table->json('tenure_factors'); // Historical data and scoring
            $table->json('ai_insights')->nullable();
            $table->timestamp('forecasted_at');
            $table->timestamp('actual_departure_date')->nullable();
            $table->integer('actual_tenure_months')->nullable();
            $table->timestamps();

            $table->index(['application_id', 'forecasted_at']);
            $table->index(['company_id', 'player_type']);
            $table->index('expected_tenure_months');
        });

        // Table 3: Productivity Estimates
        Schema::create('scout_productivity_estimates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->onDelete('cascade');
            $table->foreignId('job_id')->constrained('job_listings')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->integer('time_to_basic_productivity_days');
            $table->integer('time_to_full_productivity_days');
            $table->integer('time_to_high_productivity_days');
            $table->decimal('confidence_score', 5, 4);
            $table->json('productivity_factors'); // Skills readiness, learning speed, etc.
            $table->json('productivity_timeline'); // Milestone breakdown
            $table->json('onboarding_recommendations');
            $table->timestamp('estimated_at');
            $table->date('actual_basic_productivity_date')->nullable();
            $table->date('actual_full_productivity_date')->nullable();
            $table->date('actual_high_productivity_date')->nullable();
            $table->timestamps();

            $table->index(['application_id', 'estimated_at']);
            $table->index(['company_id', 'time_to_full_productivity_days'], 'scout_prod_est_company_time_idx');
        });

        // Table 4: Flight Risk Assessments
        Schema::create('scout_flight_risk_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->onDelete('cascade');
            $table->foreignId('job_id')->constrained('job_listings')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('risk_score', 5, 4); // 0.0000 to 1.0000
            $table->enum('risk_level', ['very_low', 'low', 'medium', 'high', 'critical']);
            $table->json('risk_factors'); // Detailed risk indicators
            $table->json('retention_strategies'); // Recommended actions
            $table->decimal('assessment_confidence', 5, 4);
            $table->timestamp('assessed_at');
            $table->boolean('risk_materialized')->nullable();
            $table->date('departure_date')->nullable();
            $table->text('departure_reason')->nullable();
            $table->timestamps();

            $table->index(['application_id', 'assessed_at']);
            $table->index(['company_id', 'risk_level']);
            $table->index('risk_score');
        });

        // Table 5: Development Needs
        Schema::create('scout_development_needs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->enum('need_type', ['skill_gap', 'knowledge_gap', 'behavioral_need', 'leadership_need']);
            $table->string('need_category', 100); // technical, soft_skills, domain, etc.
            $table->text('need_description');
            $table->enum('priority', ['low', 'medium', 'high', 'critical']);
            $table->integer('estimated_time_to_address')->nullable(); // Days
            $table->json('recommended_actions');
            $table->enum('status', ['identified', 'in_progress', 'completed', 'deferred'])->default('identified');
            $table->timestamp('identified_at')->default(now());
            $table->timestamp('completed_at')->nullable();
            $table->decimal('progress_percentage', 5, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['company_id', 'need_type']);
            $table->index('priority');
        });

        // Table 6: Career Path Predictions
        Schema::create('scout_career_path_predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('current_role', 200);
            $table->string('predicted_role', 200);
            $table->enum('path_type', ['vertical', 'lateral', 'diagonal', 'specialist', 'leadership']);
            $table->integer('estimated_timeline_months');
            $table->decimal('probability', 5, 4); // Likelihood of this path
            $table->json('required_skills');
            $table->json('development_milestones');
            $table->text('succession_notes')->nullable();
            $table->timestamp('predicted_at');
            $table->boolean('path_achieved')->nullable();
            $table->date('achievement_date')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'predicted_at']);
            $table->index(['company_id', 'path_type']);
            $table->index('probability');
        });

        // Table 7: Performance Analytics Cache (for quick dashboard access)
        Schema::create('scout_performance_analytics_cache', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('analytics_type', 100); // success_rate, avg_tenure, productivity_avg, etc.
            $table->string('period', 50); // last_30_days, last_90_days, last_year
            $table->json('analytics_data'); // The computed metrics
            $table->integer('sample_size')->default(0);
            $table->timestamp('computed_at');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['company_id', 'analytics_type', 'period'], 'scout_perf_analytics_idx');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scout_performance_analytics_cache');
        Schema::dropIfExists('scout_career_path_predictions');
        Schema::dropIfExists('scout_development_needs');
        Schema::dropIfExists('scout_flight_risk_assessments');
        Schema::dropIfExists('scout_productivity_estimates');
        Schema::dropIfExists('scout_tenure_forecasts');
        Schema::dropIfExists('scout_success_predictions');
    }
};
