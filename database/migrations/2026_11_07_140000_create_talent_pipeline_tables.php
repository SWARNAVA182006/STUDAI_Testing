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
        // Talent Pipelines - Pre-qualified candidate pools for recurring roles
        Schema::create('talent_pipelines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('pipeline_name');
            $table->string('target_role'); // Job title/category this pipeline serves
            $table->text('role_description')->nullable();
            $table->enum('pipeline_status', ['active', 'paused', 'archived'])->default('active');
            $table->enum('pipeline_type', ['recurring_role', 'critical_position', 'growth_initiative', 'succession_planning'])->default('recurring_role');
            $table->json('required_skills')->nullable(); // Skills needed for this pipeline
            $table->json('preferred_experience')->nullable(); // Experience criteria
            $table->json('cultural_fit_criteria')->nullable(); // Corporate DNA alignment factors
            $table->integer('target_pipeline_size')->default(10); // Desired number of qualified candidates
            $table->integer('current_pipeline_size')->default(0);
            $table->decimal('pipeline_health_score', 5, 2)->default(0); // 0-100 score
            $table->integer('hiring_frequency_days')->nullable(); // How often this role is hired
            $table->date('last_hired_at')->nullable();
            $table->date('next_projected_hire_date')->nullable();
            $table->json('pipeline_metrics')->nullable(); // Response rates, conversion rates, etc.
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'pipeline_status']);
            $table->index(['target_role', 'pipeline_status']);
            $table->index('pipeline_health_score');
        });

        // Pipeline Candidates - Candidates in talent pipelines
        Schema::create('pipeline_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_pipeline_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('pipeline_stage', [
                'sourced', // Discovered but not contacted
                'engaged', // Initial contact made
                'qualified', // Meets basic criteria
                'pre_screened', // Phone screen completed
                'warm', // Ready for interview when position opens
                'hot', // Actively interested, high priority
                'cool', // Interest declining, needs re-engagement
                'archived' // No longer suitable
            ])->default('sourced');
            $table->decimal('match_score', 5, 2)->default(0); // How well they fit this pipeline (0-100)
            $table->decimal('dna_compatibility_score', 5, 2)->default(0); // Corporate DNA fit (0-100)
            $table->date('added_to_pipeline_at');
            $table->date('last_engaged_at')->nullable();
            $table->integer('engagement_count')->default(0);
            $table->enum('engagement_preference', ['email', 'phone', 'linkedin', 'no_contact'])->default('email');
            $table->json('interaction_history')->nullable(); // Summary of past interactions
            $table->json('skill_assessment')->nullable(); // Skills evaluation results
            $table->text('sourcing_notes')->nullable();
            $table->text('engagement_notes')->nullable();
            $table->enum('availability_status', ['immediately_available', 'available_soon', 'passive', 'not_available'])->default('passive');
            $table->decimal('expected_salary_min', 12, 2)->nullable();
            $table->decimal('expected_salary_max', 12, 2)->nullable();
            $table->date('next_follow_up_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['talent_pipeline_id', 'user_id']);
            $table->index(['pipeline_stage', 'match_score']);
            $table->index('next_follow_up_date');
            $table->index('last_engaged_at');
        });

        // Silver Medalists - Strong candidates who weren't selected but remain valuable
        Schema::create('silver_medalists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('job_id')->constrained()->onDelete('cascade'); // Original job they applied for
            $table->foreignId('application_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('silver_medal_reason', [
                'strong_second_choice', // Narrowly missed out
                'overqualified', // Too experienced for the role
                'timing_mismatch', // Right person, wrong time
                'budget_constraints', // Salary expectations too high
                'team_fit_preference', // Another candidate was better team fit
                'skill_mismatch_minor', // Close match, small skill gap
                'cultural_potential' // Good cultural fit, developing skills
            ]);
            $table->decimal('interview_score', 5, 2)->nullable(); // Score from interview process
            $table->decimal('skill_score', 5, 2)->nullable();
            $table->decimal('cultural_fit_score', 5, 2)->nullable();
            $table->json('strengths')->nullable(); // What made them strong candidates
            $table->json('development_areas')->nullable(); // Areas for improvement
            $table->text('interviewer_feedback')->nullable();
            $table->text('ai_recommendation')->nullable(); // AI-generated recommendation for future roles
            $table->json('suitable_future_roles')->nullable(); // Job titles/categories they'd be great for
            $table->enum('re_engagement_status', ['not_contacted', 'contacted', 'interested', 'not_interested', 'hired_elsewhere'])->default('not_contacted');
            $table->date('silver_medal_date'); // When they became a silver medalist
            $table->date('last_contacted_at')->nullable();
            $table->integer('contact_attempts')->default(0);
            $table->date('next_reach_out_date')->nullable(); // When to contact them again
            $table->boolean('added_to_talent_pipeline')->default(false);
            $table->foreignId('talent_pipeline_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 're_engagement_status']);
            $table->index('next_reach_out_date');
            $table->index(['silver_medal_date', 're_engagement_status']);
        });

        // Passive Candidate Profiles - Market monitoring for candidates who match Corporate DNA
        Schema::create('passive_candidate_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade'); // Null if not yet in system
            $table->string('source_platform')->nullable(); // LinkedIn, GitHub, etc.
            $table->string('external_profile_url')->nullable();
            $table->string('candidate_name');
            $table->string('candidate_email')->nullable();
            $table->string('candidate_phone')->nullable();
            $table->string('current_company')->nullable();
            $table->string('current_title')->nullable();
            $table->string('location')->nullable();
            $table->json('skills')->nullable();
            $table->json('experience_summary')->nullable();
            $table->integer('years_of_experience')->nullable();
            $table->decimal('dna_match_score', 5, 2)->default(0); // How well they match Corporate DNA
            $table->json('dna_alignment_factors')->nullable(); // Specific DNA matches
            $table->json('potential_roles')->nullable(); // Roles they could fill
            $table->enum('discovery_method', [
                'ai_market_scan',
                'referral',
                'event_attendance',
                'social_media',
                'competitor_analysis',
                'industry_research'
            ])->default('ai_market_scan');
            $table->enum('engagement_readiness', [
                'not_ready', // Too early to reach out
                'monitor', // Keep watching for signals
                'ready', // Good time to engage
                'urgent' // Showing job search signals
            ])->default('monitor');
            $table->json('engagement_signals')->nullable(); // LinkedIn activity, job changes, etc.
            $table->date('discovered_at');
            $table->date('last_monitored_at')->nullable();
            $table->date('optimal_engagement_date')->nullable(); // AI-predicted best time to reach out
            $table->boolean('engagement_initiated')->default(false);
            $table->date('engaged_at')->nullable();
            $table->enum('engagement_outcome', ['no_response', 'interested', 'not_interested', 'converted_to_applicant', 'added_to_pipeline'])->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'engagement_readiness']);
            $table->index('dna_match_score');
            $table->index('optimal_engagement_date');
            $table->index(['engagement_initiated', 'engagement_readiness'], 'passive_candidate_engagement_index');
        });

        // Candidate Interactions - Track all touchpoints throughout candidate journey
        Schema::create('candidate_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('application_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('job_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('interaction_type', [
                'application_submitted',
                'application_viewed',
                'status_update_sent',
                'email_sent',
                'phone_call',
                'interview_scheduled',
                'interview_completed',
                'assessment_sent',
                'assessment_completed',
                'offer_extended',
                'offer_accepted',
                'offer_declined',
                'rejection_sent',
                'feedback_provided',
                'follow_up_contact',
                'pipeline_addition',
                'pipeline_engagement',
                'silver_medal_notification',
                're_engagement_attempt'
            ]);
            $table->string('interaction_channel')->nullable(); // email, phone, in-person, video, platform
            $table->text('interaction_summary')->nullable();
            $table->json('interaction_metadata')->nullable(); // Additional context
            $table->boolean('automated')->default(false); // Was this interaction automated?
            $table->foreignId('initiated_by_user_id')->nullable()->constrained('users')->onDelete('set null'); // Who initiated (if manual)
            $table->enum('candidate_sentiment', ['positive', 'neutral', 'negative', 'unknown'])->default('unknown');
            $table->text('candidate_feedback')->nullable();
            $table->integer('response_time_hours')->nullable(); // Time to respond to candidate
            $table->timestamp('interacted_at');
            $table->timestamps();

            $table->index(['user_id', 'interacted_at']);
            $table->index(['company_id', 'interaction_type']);
            $table->index('interacted_at');
        });

        // Employer Brand Scores - Track candidate experience and employer brand health
        Schema::create('employer_brand_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->date('measurement_date');
            $table->enum('measurement_period', ['daily', 'weekly', 'monthly', 'quarterly'])->default('weekly');
            
            // Overall Score
            $table->decimal('overall_brand_score', 5, 2)->default(0); // 0-100
            
            // Component Scores
            $table->decimal('application_experience_score', 5, 2)->default(0); // Application process quality
            $table->decimal('communication_score', 5, 2)->default(0); // Response times and clarity
            $table->decimal('interview_experience_score', 5, 2)->default(0); // Interview process quality
            $table->decimal('feedback_quality_score', 5, 2)->default(0); // Quality of rejection feedback
            $table->decimal('transparency_score', 5, 2)->default(0); // Process transparency
            $table->decimal('respect_score', 5, 2)->default(0); // How respectfully candidates are treated
            
            // Metrics
            $table->decimal('average_response_time_hours', 8, 2)->nullable();
            $table->integer('total_interactions')->default(0);
            $table->integer('positive_interactions')->default(0);
            $table->integer('negative_interactions')->default(0);
            $table->integer('feedback_requests_sent')->default(0);
            $table->integer('feedback_responses_received')->default(0);
            $table->decimal('feedback_response_rate', 5, 2)->default(0);
            
            // Sentiment Analysis
            $table->json('positive_feedback_themes')->nullable();
            $table->json('negative_feedback_themes')->nullable();
            $table->json('improvement_recommendations')->nullable();
            
            // Benchmarking
            $table->decimal('industry_benchmark_score', 5, 2)->nullable();
            $table->enum('brand_health_trend', ['improving', 'stable', 'declining'])->default('stable');
            
            $table->timestamps();

            $table->unique(['company_id', 'measurement_date', 'measurement_period'], 'employer_brand_scores_measurement_unique');
            $table->index(['company_id', 'measurement_date']);
            $table->index('overall_brand_score');
        });

        // Candidate Feedback - Structured feedback collection
        Schema::create('candidate_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('application_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('job_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('feedback_type', [
                'application_experience',
                'interview_experience',
                'rejection_feedback',
                'offer_feedback',
                'general_experience'
            ]);
            $table->enum('feedback_trigger', ['requested', 'voluntary', 'follow_up'])->default('requested');
            
            // Ratings (1-5 scale)
            $table->integer('overall_rating')->nullable();
            $table->integer('application_process_rating')->nullable();
            $table->integer('communication_rating')->nullable();
            $table->integer('interview_rating')->nullable();
            $table->integer('respect_rating')->nullable();
            $table->integer('transparency_rating')->nullable();
            
            // Open-ended feedback
            $table->text('positive_experience')->nullable();
            $table->text('negative_experience')->nullable();
            $table->text('improvement_suggestions')->nullable();
            $table->text('general_comments')->nullable();
            
            // Recommendation
            $table->boolean('would_recommend')->nullable();
            $table->boolean('would_apply_again')->nullable();
            
            // NPS-style metric
            $table->integer('likelihood_to_recommend')->nullable(); // 0-10 scale
            
            $table->timestamp('feedback_requested_at')->nullable();
            $table->timestamp('feedback_submitted_at')->nullable();
            $table->integer('response_time_days')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'feedback_submitted_at']);
            $table->index('overall_rating');
            $table->index('likelihood_to_recommend');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidate_feedback');
        Schema::dropIfExists('employer_brand_scores');
        Schema::dropIfExists('candidate_interactions');
        Schema::dropIfExists('passive_candidate_profiles');
        Schema::dropIfExists('silver_medalists');
        Schema::dropIfExists('pipeline_candidates');
        Schema::dropIfExists('talent_pipelines');
    }
};
