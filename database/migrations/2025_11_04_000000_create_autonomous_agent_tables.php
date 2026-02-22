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
        // Agent Configuration - User's autonomous agent settings
        Schema::create('agent_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('is_active')->default(false);
            $table->integer('daily_application_limit')->default(5); // Max applications per day
            $table->integer('applications_this_month')->default(0);
            
            // Preferences
            $table->json('target_roles')->nullable(); // ["Software Engineer", "Backend Developer"]
            $table->json('preferred_locations')->nullable(); // ["Remote", "San Francisco", "New York"]
            $table->json('required_skills')->nullable(); // Must-have skills
            $table->json('nice_to_have_skills')->nullable();
            $table->integer('min_salary')->nullable();
            $table->integer('max_salary')->nullable();
            $table->enum('salary_period', ['hourly', 'monthly', 'yearly'])->default('yearly');
            $table->json('company_sizes')->nullable(); // ["startup", "medium", "enterprise"]
            $table->json('work_arrangements')->nullable(); // ["remote", "hybrid", "onsite"]
            $table->json('employment_types')->nullable(); // ["full_time", "contract", "part_time"]
            
            // Advanced Filters
            $table->integer('min_experience_years')->nullable();
            $table->integer('max_experience_years')->nullable();
            $table->json('industries')->nullable(); // ["technology", "finance", "healthcare"]
            $table->json('excluded_keywords')->nullable(); // Job title/description keywords to avoid
            $table->boolean('only_verified_companies')->default(false);
            $table->boolean('require_visa_sponsorship')->default(false);
            
            // Application Strategy
            $table->enum('application_aggressiveness', ['conservative', 'moderate', 'aggressive'])->default('moderate');
            $table->integer('match_threshold_percentage')->default(70); // Only apply if >70% match
            $table->boolean('auto_follow_up')->default(true);
            $table->integer('follow_up_days')->default(7); // Days before auto-follow-up
            
            // Learning & Optimization
            $table->boolean('enable_learning')->default(true);
            $table->json('learning_metrics')->nullable(); // Success rates, preferred job types
            $table->timestamp('last_optimization_at')->nullable();
            
            // Scheduling
            $table->json('active_hours')->nullable(); // ["09:00-17:00"] - when to run agent
            $table->json('active_days')->nullable(); // [1,2,3,4,5] - weekdays only
            $table->timestamp('next_run_at')->nullable();
            $table->timestamp('last_run_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique('user_id');
            $table->index(['is_active', 'next_run_at']);
        });

        // Job Sources - Where the agent searches for jobs
        Schema::create('job_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // "LinkedIn", "Indeed", "Glassdoor"
            $table->string('type'); // "job_board", "company_website", "rss_feed", "api"
            $table->string('url')->nullable();
            $table->json('scraping_config')->nullable(); // Selectors, API endpoints
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(5); // 1-10, higher = more frequent
            $table->integer('success_rate')->default(0); // % of successful scrapes
            $table->timestamp('last_scraped_at')->nullable();
            $table->integer('jobs_found_today')->default(0);
            $table->integer('jobs_found_total')->default(0);
            $table->timestamps();
            
            $table->index(['is_active', 'priority']);
        });

        // Discovered Jobs - Jobs found by the agent (before matching)
        Schema::create('discovered_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_source_id')->constrained()->onDelete('cascade');
            $table->string('external_id')->nullable(); // ID from source
            $table->string('url')->unique();
            
            // Job Details
            $table->string('title');
            $table->string('company_name');
            $table->text('description');
            $table->text('requirements')->nullable();
            $table->json('extracted_skills')->nullable(); // AI-extracted skills
            $table->string('location')->nullable();
            $table->boolean('is_remote')->default(false);
            $table->string('work_arrangement')->nullable(); // "remote", "hybrid", "onsite"
            
            // Compensation
            $table->integer('salary_min')->nullable();
            $table->integer('salary_max')->nullable();
            $table->string('salary_period')->nullable(); // "yearly", "monthly", "hourly"
            $table->string('salary_currency')->default('USD');
            
            // Metadata
            $table->string('employment_type')->nullable(); // "full_time", "contract"
            $table->string('experience_level')->nullable(); // "entry", "mid", "senior"
            $table->integer('applicant_count')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            
            // Matching & Status
            $table->boolean('is_processed')->default(false);
            $table->boolean('is_duplicate')->default(false);
            $table->foreignId('duplicate_of_id')->nullable()->constrained('discovered_jobs');
            $table->json('matched_user_ids')->nullable(); // Users who match this job
            $table->float('ats_score')->nullable(); // ATS compatibility score
            
            $table->timestamps();
            
            $table->index(['job_source_id', 'is_processed']);
            $table->index(['company_name', 'title']);
            $table->index('posted_at');
            $table->fullText(['title', 'description']);
        });

        // Job Matches - Discovered jobs matched to users
        Schema::create('job_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('discovered_job_id')->constrained()->onDelete('cascade');
            
            // Match Scoring
            $table->float('overall_match_score'); // 0-100
            $table->json('score_breakdown')->nullable(); // {skills: 85, location: 100, salary: 90}
            $table->json('matching_skills')->nullable(); // Skills that matched
            $table->json('missing_skills')->nullable(); // Required skills user lacks
            
            // Agent Decision
            $table->enum('agent_decision', ['apply', 'review', 'skip'])->default('review');
            $table->text('decision_reasoning')->nullable(); // Why agent chose this action
            $table->float('confidence_score')->nullable(); // 0-100 confidence in decision
            
            // User Override
            $table->enum('user_override', ['approve', 'reject', 'pending'])->nullable();
            $table->text('user_notes')->nullable();
            
            // Application Status
            $table->boolean('has_applied')->default(false);
            $table->timestamp('applied_at')->nullable();
            $table->unsignedBigInteger('auto_application_id')->nullable();
            
            $table->timestamps();
            
            $table->unique(['user_id', 'discovered_job_id']);
            $table->index(['user_id', 'agent_decision']);
            $table->index(['overall_match_score']);
        });

        // Auto Applications - Applications submitted by the agent
        Schema::create('auto_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('job_match_id')->constrained()->onDelete('cascade');
            $table->foreignId('discovered_job_id')->constrained();
            
            // Application Materials
            $table->text('customized_resume_path')->nullable(); // S3/local path
            $table->text('customized_resume_content')->nullable(); // LaTeX/PDF content
            $table->text('cover_letter')->nullable();
            $table->json('screening_answers')->nullable(); // Q&A for application forms
            $table->json('custom_fields')->nullable(); // Platform-specific fields
            
            // Optimization
            $table->json('resume_changes')->nullable(); // What was customized
            $table->json('keywords_optimized')->nullable(); // ATS keywords added
            $table->float('ats_optimization_score')->nullable(); // 0-100
            
            // Submission
            $table->enum('submission_method', ['api', 'email', 'form', 'manual_review'])->default('manual_review');
            $table->enum('status', ['pending', 'submitted', 'failed', 'requires_manual'])->default('pending');
            $table->text('submission_response')->nullable();
            $table->timestamp('submitted_at')->nullable();
            
            // Tracking
            $table->enum('application_status', [
                'submitted', 
                'viewed', 
                'screening', 
                'interviewing', 
                'offered', 
                'rejected',
                'withdrawn',
                'ghosted'
            ])->default('submitted');
            $table->timestamp('status_updated_at')->nullable();
            $table->json('status_history')->nullable(); // Timeline of status changes
            
            // Follow-ups
            $table->boolean('follow_up_sent')->default(false);
            $table->timestamp('follow_up_at')->nullable();
            $table->integer('follow_up_count')->default(0);
            
            // Learning Data
            $table->boolean('got_response')->default(false);
            $table->boolean('got_interview')->default(false);
            $table->boolean('got_offer')->default(false);
            $table->text('rejection_reason')->nullable();
            $table->json('feedback')->nullable(); // From employer or AI analysis
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['user_id', 'status']);
            $table->index(['application_status', 'submitted_at']);
            $table->index('created_at'); // For daily limits
        });

        // Application Activity Log - Detailed log of all agent actions
        Schema::create('application_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('auto_application_id')->nullable()->constrained();
            $table->foreignId('discovered_job_id')->nullable()->constrained();
            
            $table->string('action_type'); // "job_discovered", "application_submitted", "follow_up_sent"
            $table->text('description');
            $table->json('metadata')->nullable(); // Additional context
            $table->enum('severity', ['info', 'success', 'warning', 'error'])->default('info');
            
            $table->timestamp('created_at');
            
            $table->index(['user_id', 'created_at']);
            $table->index('action_type');
        });

        // Company Blacklist - Companies/jobs to avoid
        Schema::create('company_blacklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('company_name');
            $table->string('reason')->nullable(); // "poor_reviews", "bad_experience", "spam"
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'company_name']);
        });

        // Agent Learning Metrics - Machine learning data
        Schema::create('agent_learning_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Success Patterns
            $table->json('successful_job_patterns')->nullable(); // Common traits of jobs that led to interviews
            $table->json('unsuccessful_job_patterns')->nullable(); // Patterns to avoid
            $table->json('keyword_performance')->nullable(); // Which keywords led to success
            $table->json('company_type_performance')->nullable(); // Startup vs enterprise success rate
            
            // Application Quality
            $table->float('average_match_score_applied')->nullable();
            $table->float('average_response_rate')->nullable(); // % of applications that got responses
            $table->float('average_interview_rate')->nullable(); // % that led to interviews
            
            // Optimization Insights
            $table->json('best_application_times')->nullable(); // Time of day/week with best results
            $table->json('resume_optimization_effectiveness')->nullable();
            $table->json('cover_letter_templates_performance')->nullable();
            
            // Learning Cycle
            $table->integer('total_applications')->default(0);
            $table->integer('total_responses')->default(0);
            $table->integer('total_interviews')->default(0);
            $table->integer('total_offers')->default(0);
            $table->timestamp('last_learning_cycle_at')->nullable();
            
            $table->timestamps();
            
            $table->unique('user_id');
        });

        // Application Templates - Reusable templates optimized over time
        Schema::create('application_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            $table->string('name');
            $table->enum('type', ['cover_letter', 'resume', 'screening_answers']);
            $table->text('content');
            $table->json('variables')->nullable(); // Placeholders: {company_name}, {role}
            $table->json('target_roles')->nullable(); // Which roles this template works for
            
            // Performance
            $table->integer('times_used')->default(0);
            $table->float('success_rate')->nullable(); // % that led to responses
            $table->float('average_match_score')->nullable();
            
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            
            $table->index(['user_id', 'type']);
        });

        // Add foreign key for job_matches.auto_application_id after auto_applications table exists
        Schema::table('job_matches', function (Blueprint $table) {
            $table->foreign('auto_application_id')->references('id')->on('auto_applications')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_matches', function (Blueprint $table) {
            $table->dropForeign(['auto_application_id']);
        });
        
        Schema::dropIfExists('application_templates');
        Schema::dropIfExists('agent_learning_metrics');
        Schema::dropIfExists('company_blacklists');
        Schema::dropIfExists('application_activity_logs');
        Schema::dropIfExists('auto_applications');
        Schema::dropIfExists('job_matches');
        Schema::dropIfExists('discovered_jobs');
        Schema::dropIfExists('job_sources');
        Schema::dropIfExists('agent_configurations');
    }
};
