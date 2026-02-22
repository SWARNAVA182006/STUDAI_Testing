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
        // Table 1: Anonymized Screenings
        Schema::create('scout_anonymized_screenings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->onDelete('cascade');
            $table->foreignId('job_id')->constrained('jobs')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('users')->onDelete('cascade');
            
            // Anonymization details
            $table->string('anonymized_id')->unique()->index(); // e.g., ANON_X7K9P2M4L8Q1
            $table->json('anonymized_data'); // Qualification-only data without identifying info
            $table->string('original_data_hash'); // SHA256 hash for verification
            $table->enum('anonymization_level', ['minimal', 'standard', 'strict'])->default('standard');
            $table->json('removed_attributes'); // List of attributes that were removed
            
            // Status and lifecycle
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('expires_at')->nullable()->index(); // Auto-expire after hiring decision
            $table->timestamp('deanonymized_at')->nullable(); // When deanonymized (after hire)
            $table->foreignId('deanonymized_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
            
            // Indexes
            $table->index('company_id');
            $table->index('job_id');
            $table->index(['company_id', 'is_active']);
            $table->index(['expires_at', 'is_active']);
        });

        // Table 2: Bias Audit Results
        Schema::create('scout_bias_audit_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('users')->onDelete('cascade');
            
            // Audit period
            $table->timestamp('audit_period_start');
            $table->timestamp('audit_period_end');
            $table->integer('total_applications_analyzed')->default(0);
            
            // Bias metrics
            $table->decimal('bias_score', 5, 4)->index(); // 0.0000 to 1.0000 (0 = no bias, 1 = severe)
            $table->enum('fairness_rating', ['excellent', 'good', 'fair', 'needs_improvement', 'concerning'])->index();
            
            // Analysis results
            $table->json('demographic_analysis'); // Selection rate parity, advancement rates, offer consistency
            $table->json('proxy_discrimination_findings'); // Alerts for proxy indicators
            $table->json('decision_patterns'); // Rejection patterns, criteria consistency
            $table->json('fairness_metrics'); // Disparate impact, selection rate variance, etc.
            $table->json('ai_detected_patterns')->nullable(); // GPT-4 analysis results
            
            // Recommendations and actions
            $table->json('recommendations'); // Actionable steps to improve fairness
            $table->boolean('requires_attention')->default(false)->index();
            $table->timestamp('attention_acknowledged_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Follow-up
            $table->text('action_taken')->nullable();
            $table->timestamp('remediation_completed_at')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('company_id');
            $table->index(['company_id', 'fairness_rating']);
            $table->index(['company_id', 'requires_attention']);
            $table->index('audit_period_start');
        });

        // Table 3: Fairness Metrics (Detailed Tracking)
        Schema::create('scout_fairness_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('audit_id')->nullable()->constrained('scout_bias_audit_results')->onDelete('cascade');
            
            // Metric details
            $table->string('metric_type')->index(); // disparate_impact, selection_rate, offer_rate, etc.
            $table->string('metric_category')->index(); // demographic, proxy, outcome, process
            $table->decimal('metric_value', 8, 4); // The calculated value
            $table->decimal('threshold_value', 8, 4)->nullable(); // Acceptable threshold (e.g., 0.80 for 80% rule)
            $table->boolean('passes_threshold')->default(true)->index();
            
            // Context
            $table->string('dimension')->nullable(); // What dimension this metric applies to
            $table->integer('sample_size')->default(0); // Number of cases analyzed
            $table->decimal('confidence_level', 5, 4)->nullable(); // Statistical confidence
            $table->decimal('p_value', 6, 5)->nullable(); // Statistical significance
            
            // Trend tracking
            $table->decimal('previous_value', 8, 4)->nullable();
            $table->enum('trend', ['improving', 'stable', 'declining', 'new'])->default('new');
            
            // Details
            $table->json('calculation_details')->nullable(); // How the metric was calculated
            $table->text('interpretation')->nullable(); // What this metric means
            $table->text('recommendation')->nullable(); // What to do if metric is concerning
            
            $table->timestamp('measured_at');
            $table->timestamps();
            
            // Indexes
            $table->index('company_id');
            $table->index(['company_id', 'metric_type']);
            $table->index(['company_id', 'passes_threshold']);
            $table->index('measured_at');
        });

        // Table 4: Proxy Discrimination Alerts
        Schema::create('scout_proxy_discrimination_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('audit_id')->nullable()->constrained('scout_bias_audit_results')->onDelete('cascade');
            
            // Alert details
            $table->string('indicator_type')->index(); // zip_code, university_name, graduation_year, etc.
            $table->enum('discrimination_type', [
                'geographic', 'socioeconomic', 'age_proxy', 'ethnic_proxy', 'cultural_proxy', 'other'
            ])->index();
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium')->index();
            
            // Correlation analysis
            $table->decimal('correlation_strength', 5, 4); // 0.0000 to 1.0000
            $table->integer('cases_analyzed')->default(0);
            $table->decimal('statistical_significance', 6, 5)->nullable(); // p-value
            
            // Impact
            $table->text('impact_description');
            $table->json('affected_criteria')->nullable(); // Which assessment criteria are affected
            $table->json('example_cases')->nullable(); // Anonymized examples (max 3)
            
            // Recommendations
            $table->text('recommendation');
            $table->json('suggested_actions')->nullable();
            
            // Status and resolution
            $table->enum('status', ['pending_review', 'acknowledged', 'investigating', 'resolved', 'false_positive'])->default('pending_review')->index();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('company_id');
            $table->index(['company_id', 'severity']);
            $table->index(['company_id', 'status']);
            $table->index(['indicator_type', 'discrimination_type'], 'proxy_discrimination_indicator_idx');
        });

        // Table 5: Decision Explanations (Explainable AI)
        Schema::create('scout_decision_explanations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->onDelete('cascade');
            $table->foreignId('job_id')->constrained('jobs')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('users')->onDelete('cascade');
            
            // Decision details
            $table->enum('decision_type', [
                'shortlist', 'reject', 'interview_invite', 'offer', 'final_reject'
            ])->index();
            $table->timestamp('decision_made_at');
            
            // Explanation
            $table->json('primary_factors'); // Top 5 factors with importance scores
            $table->text('explanation'); // Human-readable explanation
            $table->decimal('confidence_score', 5, 4); // AI confidence in decision
            $table->decimal('transparency_score', 5, 4); // How explainable the decision is
            
            // Bias checking
            $table->json('bias_indicators')->nullable(); // Any detected bias signals
            $table->boolean('human_review_recommended')->default(false)->index();
            $table->text('bias_concerns')->nullable();
            
            // Factor breakdown
            $table->json('all_factors')->nullable(); // Complete factor list with scores
            $table->json('criteria_weights')->nullable(); // Weights used for each criterion
            $table->json('candidate_scores')->nullable(); // Scores on each criterion
            
            // Accountability
            $table->boolean('human_reviewed')->default(false)->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('reviewer_notes')->nullable();
            $table->boolean('decision_overridden')->default(false);
            $table->text('override_reason')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('company_id');
            $table->index(['company_id', 'decision_type']);
            $table->index(['company_id', 'human_review_recommended'], 'decision_human_review_idx');
            $table->index('decision_made_at');
        });

        // Table 6: Diversity Analytics (Aggregated Privacy-Preserving)
        Schema::create('scout_diversity_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('users')->onDelete('cascade');
            
            // Reporting period
            $table->date('period_start');
            $table->date('period_end');
            $table->enum('period_type', ['weekly', 'monthly', 'quarterly', 'annual'])->index();
            
            // Aggregate metrics (NEVER individual data)
            $table->integer('total_applications')->default(0);
            $table->integer('total_hires')->default(0);
            $table->integer('minimum_group_size')->default(10); // Privacy threshold
            
            // Funnel diversity
            $table->json('application_stage_distribution')->nullable(); // Applied, screened, interviewed, offered, hired
            $table->json('role_distribution')->nullable(); // Engineering, management, design, etc.
            $table->json('seniority_distribution')->nullable(); // Junior, mid, senior
            
            // Retention (aggregate only)
            $table->decimal('overall_retention_rate', 5, 2)->nullable(); // Percentage
            $table->json('retention_by_cohort')->nullable(); // Cohort analysis (groups of 10+)
            $table->decimal('avg_tenure_months', 6, 2)->nullable();
            
            // Pay equity (aggregate)
            $table->decimal('pay_equity_score', 5, 4)->nullable(); // 0-1 scale
            $table->json('compensation_distribution')->nullable(); // Ranges, not individuals
            $table->json('pay_gap_analysis')->nullable(); // Statistical analysis only
            
            // Inclusion metrics (aggregate)
            $table->json('inclusion_metrics')->nullable(); // Survey results, participation rates
            $table->decimal('inclusion_index', 5, 2)->nullable(); // 0-100 scale
            
            // Compliance
            $table->boolean('meets_privacy_threshold')->default(true); // All groups >= 10
            $table->text('privacy_notes')->nullable();
            $table->boolean('data_anonymized')->default(true);
            
            // Trends
            $table->enum('diversity_trend', ['improving', 'stable', 'declining'])->nullable();
            $table->enum('inclusion_trend', ['improving', 'stable', 'declining'])->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('company_id');
            $table->index(['company_id', 'period_type']);
            $table->index('period_start');
            $table->index(['company_id', 'period_start', 'period_end'], 'diversity_period_span_idx');
        });

        // Table 7: Bias Mitigation Actions (Audit Trail)
        Schema::create('scout_bias_mitigation_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('audit_id')->nullable()->constrained('scout_bias_audit_results')->onDelete('cascade');
            $table->foreignId('alert_id')->nullable()->constrained('scout_proxy_discrimination_alerts')->onDelete('cascade');
            
            // Action details
            $table->enum('action_type', [
                'criteria_adjustment', 'process_change', 'training', 'policy_update', 
                'technology_update', 'review_process', 'other'
            ])->index();
            $table->string('action_title');
            $table->text('action_description');
            
            // Planning
            $table->timestamp('planned_start_date')->nullable();
            $table->timestamp('planned_completion_date')->nullable();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Execution
            $table->enum('status', [
                'planned', 'in_progress', 'completed', 'delayed', 'cancelled'
            ])->default('planned')->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('progress_percentage')->default(0);
            
            // Impact tracking
            $table->json('expected_impact')->nullable();
            $table->json('actual_impact')->nullable();
            $table->text('impact_notes')->nullable();
            
            // Follow-up
            $table->boolean('requires_verification')->default(true);
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->decimal('effectiveness_score', 5, 4)->nullable(); // 0-1, measured after completion
            
            $table->timestamps();
            
            // Indexes
            $table->index('company_id');
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'action_type']);
        });

        // Table 8: Training Data Diversity Validation
        Schema::create('scout_training_data_validation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('users')->onDelete('cascade');
            
            // Dataset details
            $table->string('dataset_type')->index(); // historical_hires, assessment_scores, interviews, etc.
            $table->integer('total_records')->default(0);
            $table->timestamp('data_period_start');
            $table->timestamp('data_period_end');
            
            // Diversity metrics
            $table->json('representation_metrics'); // Distribution across various dimensions
            $table->decimal('diversity_score', 5, 4); // 0-1, higher is more diverse
            $table->boolean('meets_diversity_threshold')->default(false)->index();
            $table->decimal('minimum_threshold', 5, 4)->default(0.70); // Configurable threshold
            
            // Imbalance detection
            $table->json('imbalanced_dimensions')->nullable(); // Dimensions with poor representation
            $table->json('underrepresented_groups')->nullable(); // Groups below threshold
            $table->integer('smallest_group_size')->nullable();
            
            // Quality metrics
            $table->integer('missing_data_count')->default(0);
            $table->decimal('data_quality_score', 5, 4)->nullable();
            $table->json('data_quality_issues')->nullable();
            
            // Recommendations
            $table->json('recommendations')->nullable();
            $table->boolean('approved_for_training')->default(false)->index();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            
            // Validation
            $table->timestamp('validated_at');
            $table->foreignId('validated_by')->constrained('users')->onDelete('cascade');
            
            $table->timestamps();
            
            // Indexes
            $table->index('company_id');
            $table->index(['company_id', 'dataset_type']);
            $table->index(['company_id', 'meets_diversity_threshold'], 'training_diversity_threshold_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scout_training_data_validation');
        Schema::dropIfExists('scout_bias_mitigation_actions');
        Schema::dropIfExists('scout_diversity_analytics');
        Schema::dropIfExists('scout_decision_explanations');
        Schema::dropIfExists('scout_proxy_discrimination_alerts');
        Schema::dropIfExists('scout_fairness_metrics');
        Schema::dropIfExists('scout_bias_audit_results');
        Schema::dropIfExists('scout_anonymized_screenings');
    }
};
