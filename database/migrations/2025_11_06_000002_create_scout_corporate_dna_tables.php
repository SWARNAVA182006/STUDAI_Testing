<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates tables for S.C.O.U.T. AI Hiring System - Corporate DNA Decoder
     */
    public function up(): void
    {
        // Company DNA Profiles - Core organizational identity analysis
        Schema::create('company_dna_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            
            // Mission, Vision, Values Analysis
            $table->text('mission_statement')->nullable();
            $table->text('vision_statement')->nullable();
            $table->json('core_values')->nullable(); // ['innovation', 'integrity', 'collaboration']
            
            // AI-Generated DNA Profile
            $table->json('cultural_dna')->nullable(); // AI analysis of company culture
            $table->json('success_traits')->nullable(); // Traits of successful employees
            $table->json('work_style_preferences')->nullable(); // ['remote_friendly', 'fast_paced', 'hierarchical']
            $table->json('communication_patterns')->nullable(); // ['async', 'meetings_heavy', 'slack_first']
            $table->json('decision_making_style')->nullable(); // ['consensus', 'top_down', 'data_driven']
            
            // Organizational Characteristics
            $table->string('company_size_category')->nullable(); // startup, scaleup, enterprise
            $table->string('growth_stage')->nullable(); // seed, series_a, mature
            $table->string('industry_vertical')->nullable();
            $table->integer('employee_count')->nullable();
            $table->decimal('avg_tenure_months', 5, 1)->nullable();
            $table->decimal('retention_rate_1yr', 5, 2)->nullable(); // Percentage
            $table->decimal('promotion_rate', 5, 2)->nullable();
            
            // DNA Confidence Scores
            $table->integer('dna_completeness_score')->default(0); // 0-100
            $table->integer('data_quality_score')->default(0); // 0-100
            $table->integer('analysis_confidence')->default(0); // 0-100
            
            // Analysis Metadata
            $table->timestamp('last_analyzed_at')->nullable();
            $table->integer('total_employees_analyzed')->default(0);
            $table->integer('total_hires_analyzed')->default(0);
            $table->json('ai_analysis_summary')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('company_id');
            $table->index('dna_completeness_score');
            $table->index('last_analyzed_at');
        });

        // Culture Analysis - Deep dive into organizational culture
        Schema::create('culture_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_dna_profile_id')->constrained()->onDelete('cascade');
            
            // Culture Dimensions (Hofstede-inspired)
            $table->integer('power_distance_score')->nullable(); // 0-100 (hierarchical vs flat)
            $table->integer('individualism_score')->nullable(); // 0-100 (individual vs collective)
            $table->integer('uncertainty_avoidance_score')->nullable(); // 0-100 (risk tolerance)
            $table->integer('long_term_orientation_score')->nullable(); // 0-100 (short vs long term)
            $table->integer('indulgence_score')->nullable(); // 0-100 (work-life balance)
            
            // Work Environment
            $table->json('office_culture')->nullable(); // ['collaborative_spaces', 'quiet_zones', 'remote_first']
            $table->json('meeting_culture')->nullable(); // frequency, style, effectiveness
            $table->json('feedback_culture')->nullable(); // ['continuous', 'annual', 'peer_review']
            $table->json('recognition_patterns')->nullable(); // How success is celebrated
            
            // Team Collaboration Patterns
            $table->json('collaboration_tools')->nullable(); // ['slack', 'teams', 'asana']
            $table->decimal('avg_team_size', 4, 1)->nullable();
            $table->integer('cross_functional_score')->default(0); // 0-100
            $table->integer('autonomy_score')->default(0); // 0-100
            
            // Innovation & Learning
            $table->integer('innovation_index')->default(0); // 0-100
            $table->integer('learning_culture_score')->default(0); // 0-100
            $table->json('professional_development')->nullable(); // Training opportunities
            $table->boolean('has_mentorship_program')->default(false);
            
            // Diversity & Inclusion
            $table->json('diversity_metrics')->nullable(); // Gender, age, background diversity
            $table->integer('inclusion_score')->default(0); // 0-100
            $table->json('dei_initiatives')->nullable();
            
            // AI Insights
            $table->json('culture_strengths')->nullable(); // AI-identified strengths
            $table->json('culture_challenges')->nullable(); // Areas for improvement
            $table->json('culture_archetypes')->nullable(); // ['innovative', 'traditional', 'fast_paced']
            $table->text('ai_culture_summary')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('company_dna_profile_id');
            $table->index('innovation_index');
            $table->index('learning_culture_score');
        });

        // Hiring Patterns - Historical recruitment data analysis
        Schema::create('hiring_patterns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('job_id')->nullable()->constrained()->onDelete('set null');
            
            // Hiring Source Analysis
            $table->json('source_effectiveness')->nullable(); // LinkedIn, referrals, etc.
            $table->json('channel_conversion_rates')->nullable();
            $table->string('best_performing_channel')->nullable();
            
            // Timeline Metrics
            $table->decimal('avg_time_to_hire_days', 5, 1)->nullable();
            $table->decimal('avg_time_to_fill_days', 5, 1)->nullable();
            $table->integer('avg_candidates_per_role')->nullable();
            $table->integer('avg_interviews_per_hire')->nullable();
            
            // Success Patterns
            $table->json('successful_hire_characteristics')->nullable(); // Common traits
            $table->json('unsuccessful_hire_patterns')->nullable(); // Red flags
            $table->json('top_performer_traits')->nullable();
            $table->json('quick_departure_indicators')->nullable(); // Early turnover predictors
            
            // Skill & Experience Patterns
            $table->json('optimal_experience_ranges')->nullable(); // Years per role type
            $table->json('essential_skills_by_role')->nullable();
            $table->json('nice_to_have_skills')->nullable();
            $table->json('overvalued_credentials')->nullable(); // Degrees/certs that don't correlate with success
            
            // Education & Background
            $table->json('education_correlation')->nullable(); // Does degree matter?
            $table->json('previous_company_patterns')->nullable(); // Startups vs corporates
            $table->json('industry_transition_success')->nullable();
            
            // Compensation Analysis
            $table->json('compensation_benchmarks')->nullable();
            $table->json('offer_acceptance_rate_by_range')->nullable();
            $table->decimal('avg_negotiation_percentage', 5, 2)->nullable();
            
            // Interview Performance Correlations
            $table->json('interview_score_vs_performance')->nullable();
            $table->json('assessment_score_vs_performance')->nullable();
            $table->json('reference_check_correlation')->nullable();
            
            // Retention Patterns
            $table->json('retention_by_hire_source')->nullable();
            $table->json('retention_by_experience_level')->nullable();
            $table->json('promotion_rate_by_hire_source')->nullable();
            
            // AI Predictions
            $table->json('predicted_high_performer_profile')->nullable();
            $table->json('predicted_flight_risk_profile')->nullable();
            $table->json('cultural_fit_predictors')->nullable();
            $table->text('ai_hiring_recommendations')->nullable();
            
            // Analysis Period
            $table->date('analysis_start_date')->nullable();
            $table->date('analysis_end_date')->nullable();
            $table->integer('total_hires_in_period')->default(0);
            $table->integer('confidence_score')->default(0); // 0-100
            
            $table->timestamps();
            
            // Indexes
            $table->index('company_id');
            $table->index(['analysis_start_date', 'analysis_end_date']);
            $table->index('confidence_score');
        });

        // Success Indicators - What makes employees successful at this company
        Schema::create('success_indicators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // Reference employee
            
            // Performance Metrics
            $table->string('employee_type')->nullable(); // top_performer, average, underperformer
            $table->integer('tenure_months')->nullable();
            $table->integer('promotions_count')->default(0);
            $table->decimal('performance_rating', 3, 2)->nullable(); // 1.00-5.00
            $table->boolean('is_culture_champion')->default(false);
            
            // Skills & Competencies
            $table->json('technical_skills')->nullable();
            $table->json('soft_skills')->nullable();
            $table->json('leadership_qualities')->nullable();
            $table->json('domain_expertise')->nullable();
            
            // Work Style Traits
            $table->json('work_preferences')->nullable(); // ['remote', 'collaborative', 'autonomous']
            $table->json('communication_style')->nullable(); // ['direct', 'diplomatic', 'data_driven']
            $table->json('problem_solving_approach')->nullable();
            $table->json('learning_style')->nullable();
            
            // Cultural Alignment
            $table->integer('values_alignment_score')->default(0); // 0-100
            $table->integer('culture_fit_score')->default(0); // 0-100
            $table->integer('team_collaboration_score')->default(0); // 0-100
            $table->integer('initiative_score')->default(0); // 0-100
            
            // Background Characteristics
            $table->string('education_level')->nullable();
            $table->json('previous_companies')->nullable();
            $table->integer('years_of_experience_at_hire')->nullable();
            $table->string('hire_source')->nullable();
            
            // Career Trajectory
            $table->json('promotion_path')->nullable(); // Timeline of promotions
            $table->json('skill_development_path')->nullable();
            $table->json('project_successes')->nullable();
            $table->decimal('impact_score', 5, 2)->nullable(); // Business impact
            
            // Team Dynamics Contribution
            $table->integer('peer_feedback_score')->default(0); // 0-100
            $table->integer('mentorship_activity')->default(0); // Times mentored others
            $table->boolean('is_knowledge_sharer')->default(false);
            $table->json('collaboration_metrics')->nullable();
            
            // AI Analysis
            $table->json('success_factors')->nullable(); // AI-identified reasons for success
            $table->json('unique_strengths')->nullable();
            $table->json('transferable_patterns')->nullable(); // Applicable to future hires
            $table->text('ai_success_summary')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('company_id');
            $table->index('employee_type');
            $table->index('performance_rating');
            $table->index('culture_fit_score');
        });

        // Team Dynamics - Collaboration and interaction patterns
        Schema::create('team_dynamics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('team_name')->nullable();
            $table->string('department')->nullable();
            
            // Team Composition
            $table->integer('team_size')->nullable();
            $table->json('role_distribution')->nullable(); // Mix of seniority levels
            $table->json('skill_diversity')->nullable();
            $table->decimal('avg_team_tenure_months', 5, 1)->nullable();
            
            // Collaboration Metrics
            $table->integer('collaboration_frequency_score')->default(0); // 0-100
            $table->integer('cross_team_collaboration_score')->default(0); // 0-100
            $table->decimal('meeting_hours_per_week', 4, 1)->nullable();
            $table->integer('async_communication_score')->default(0); // 0-100
            
            // Communication Patterns
            $table->json('communication_channels_usage')->nullable(); // Slack, email, meetings
            $table->json('response_time_patterns')->nullable();
            $table->json('preferred_collaboration_times')->nullable();
            $table->string('communication_style')->nullable(); // formal, casual, hybrid
            
            // Team Performance
            $table->integer('team_performance_score')->default(0); // 0-100
            $table->integer('velocity_score')->default(0); // 0-100 (delivery speed)
            $table->integer('quality_score')->default(0); // 0-100
            $table->integer('innovation_score')->default(0); // 0-100
            
            // Psychological Safety
            $table->integer('psychological_safety_score')->default(0); // 0-100
            $table->integer('trust_level')->default(0); // 0-100
            $table->integer('openness_to_feedback_score')->default(0); // 0-100
            $table->boolean('has_healthy_conflict')->default(false);
            
            // Leadership Style
            $table->string('leadership_approach')->nullable(); // servant, directive, coaching
            $table->integer('autonomy_level')->default(0); // 0-100
            $table->integer('decision_making_speed')->default(0); // 0-100
            $table->json('leadership_effectiveness_metrics')->nullable();
            
            // Team Culture
            $table->json('team_values')->nullable();
            $table->json('working_agreements')->nullable();
            $table->json('celebration_rituals')->nullable();
            $table->json('knowledge_sharing_practices')->nullable();
            
            // Onboarding Success
            $table->decimal('avg_onboarding_time_days', 5, 1)->nullable();
            $table->integer('new_hire_integration_score')->default(0); // 0-100
            $table->json('onboarding_best_practices')->nullable();
            
            // Candidate Fit Indicators
            $table->json('ideal_new_hire_traits')->nullable(); // What works for this team
            $table->json('personality_balance_needed')->nullable(); // Introvert/extrovert mix
            $table->json('skill_gaps_to_fill')->nullable();
            $table->json('cultural_additions_needed')->nullable(); // Diversity needs
            
            // AI Insights
            $table->json('team_strengths')->nullable();
            $table->json('team_growth_areas')->nullable();
            $table->json('compatibility_patterns')->nullable(); // Who works well together
            $table->text('ai_team_summary')->nullable();
            
            // Analysis Metadata
            $table->timestamp('last_analyzed_at')->nullable();
            $table->integer('data_points_analyzed')->default(0);
            
            $table->timestamps();
            
            // Indexes
            $table->index('company_id');
            $table->index('department');
            $table->index('team_performance_score');
            $table->index('psychological_safety_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_dynamics');
        Schema::dropIfExists('success_indicators');
        Schema::dropIfExists('hiring_patterns');
        Schema::dropIfExists('culture_analyses');
        Schema::dropIfExists('company_dna_profiles');
    }
};
