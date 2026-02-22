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
        // Market data snapshots - aggregated market intelligence data
        Schema::create('market_data_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('snapshot_type'); // 'job_market', 'salary', 'skills', 'roles'
            $table->string('role')->nullable()->index(); // Job role/title
            $table->string('location')->nullable()->index(); // City/region
            $table->string('industry')->nullable()->index();
            $table->integer('sample_size')->default(0); // Number of data points analyzed
            
            // Market metrics
            $table->json('metrics'); // Key market metrics (demand, supply, growth rate, etc.)
            $table->json('salary_data')->nullable(); // Salary percentiles and ranges
            $table->json('skill_distribution')->nullable(); // Most demanded skills
            $table->json('trend_indicators')->nullable(); // Growth/decline indicators
            
            // AI insights
            $table->text('ai_analysis')->nullable(); // GPT-4 market analysis
            $table->json('predictions')->nullable(); // Future trend predictions
            $table->decimal('confidence_score', 5, 2)->default(0); // AI confidence 0-100
            
            // Metadata
            $table->date('snapshot_date')->index(); // Date of snapshot
            $table->timestamp('analyzed_at')->nullable();
            $table->timestamps();
            
            // Composite index for efficient querying
            $table->index(['snapshot_type', 'role', 'location', 'snapshot_date'], 'market_snapshots_lookup_idx');
        });

        // User market positions - individual user positioning in market
        Schema::create('user_market_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Market readiness score
            $table->decimal('readiness_score', 5, 2)->default(0); // 0-100
            $table->json('readiness_breakdown'); // Breakdown by category
            
            // Percentile rankings
            $table->decimal('overall_percentile', 5, 2)->nullable(); // Overall market position
            $table->decimal('experience_percentile', 5, 2)->nullable();
            $table->decimal('skills_percentile', 5, 2)->nullable();
            $table->decimal('compensation_percentile', 5, 2)->nullable();
            
            // Competitive analysis
            $table->json('competitive_advantages'); // User's strengths
            $table->json('competitive_weaknesses'); // Areas for improvement
            $table->json('skill_gaps'); // Missing skills for target roles
            
            // Market fit
            $table->json('best_fit_roles'); // Top matching roles with scores
            $table->json('trending_opportunities'); // Emerging roles user qualifies for
            $table->json('roles_to_avoid'); // Declining roles
            
            // Recommendations
            $table->json('recommendations'); // Personalized action items
            $table->integer('recommendation_priority')->default(5); // 1-10 urgency
            
            // Tracking
            $table->timestamp('calculated_at');
            $table->timestamp('next_update_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'calculated_at']);
        });

        // Salary trends - salary movement tracking
        Schema::create('salary_trends', function (Blueprint $table) {
            $table->id();
            $table->string('role')->index();
            $table->string('location')->index();
            $table->string('industry')->nullable();
            $table->integer('experience_years')->nullable()->index();
            
            // Salary statistics
            $table->decimal('min_salary', 12, 2);
            $table->decimal('max_salary', 12, 2);
            $table->decimal('median_salary', 12, 2);
            $table->decimal('average_salary', 12, 2);
            $table->decimal('percentile_25', 12, 2)->nullable();
            $table->decimal('percentile_75', 12, 2)->nullable();
            $table->decimal('percentile_90', 12, 2)->nullable();
            
            // Trend data
            $table->decimal('month_over_month_change', 5, 2)->default(0); // Percentage
            $table->decimal('year_over_year_change', 5, 2)->default(0); // Percentage
            $table->string('trend_direction')->default('stable'); // 'rising', 'falling', 'stable'
            $table->decimal('predicted_change_6m', 5, 2)->nullable(); // 6-month prediction
            $table->decimal('predicted_change_12m', 5, 2)->nullable(); // 12-month prediction
            
            // Market context
            $table->integer('job_postings_count')->default(0);
            $table->integer('active_candidates')->nullable();
            $table->decimal('supply_demand_ratio', 5, 2)->nullable();
            
            // Metadata
            $table->string('currency', 3)->default('INR');
            $table->integer('sample_size')->default(0);
            $table->date('trend_date')->index();
            $table->timestamps();
            
            $table->index(['role', 'location', 'trend_date']);
        });

        // Skill trends - skill demand tracking
        Schema::create('skill_trends', function (Blueprint $table) {
            $table->id();
            $table->string('skill_name')->index();
            $table->string('skill_category')->nullable(); // 'technical', 'soft', 'domain'
            $table->string('related_role')->nullable()->index();
            
            // Demand metrics
            $table->integer('demand_score')->default(0); // 0-100
            $table->integer('job_mentions_count')->default(0);
            $table->decimal('mention_frequency', 5, 2)->default(0); // Percentage of jobs
            $table->decimal('growth_rate', 5, 2)->default(0); // Percentage change
            
            // Value metrics
            $table->decimal('salary_premium', 5, 2)->nullable(); // % increase vs. without skill
            $table->decimal('interview_rate_boost', 5, 2)->nullable();
            $table->integer('value_score')->default(0); // 0-100 overall value
            
            // Trend indicators
            $table->string('trend_status')->default('stable'); // 'emerging', 'hot', 'stable', 'declining', 'obsolete'
            $table->integer('trend_velocity')->default(0); // Rate of change -100 to +100
            $table->decimal('predicted_demand_6m', 5, 2)->nullable();
            $table->decimal('predicted_demand_12m', 5, 2)->nullable();
            
            // Related data
            $table->json('related_skills')->nullable(); // Skills commonly paired with this one
            $table->json('replacement_skills')->nullable(); // Skills replacing this one
            $table->text('market_insight')->nullable(); // AI analysis
            
            // Metadata
            $table->date('trend_date')->index();
            $table->timestamps();
            
            $table->index(['skill_name', 'trend_date']);
            $table->index(['trend_status', 'demand_score']);
        });

        // Role predictions - future role demand predictions
        Schema::create('role_predictions', function (Blueprint $table) {
            $table->id();
            $table->string('role_title')->index();
            $table->string('industry')->nullable();
            $table->string('location')->nullable();
            
            // Current state
            $table->integer('current_demand_score')->default(0); // 0-100
            $table->integer('current_job_count')->default(0);
            $table->decimal('current_avg_salary', 12, 2)->nullable();
            
            // Predictions
            $table->integer('predicted_demand_3m')->nullable();
            $table->integer('predicted_demand_6m')->nullable();
            $table->integer('predicted_demand_12m')->nullable();
            $table->decimal('predicted_salary_change', 5, 2)->nullable(); // Percentage
            
            // Classification
            $table->string('role_status')->default('stable'); // 'emerging', 'growing', 'stable', 'declining', 'obsolete'
            $table->integer('emergence_score')->default(0); // 0-100 for new roles
            $table->integer('stability_score')->default(50); // 0-100 job security
            
            // AI insights
            $table->text('ai_rationale')->nullable(); // Why is this role trending/declining
            $table->json('key_drivers')->nullable(); // Factors affecting demand
            $table->json('required_skills')->nullable(); // Skills needed for this role
            $table->json('similar_roles')->nullable(); // Related job titles
            
            // Market context
            $table->integer('hiring_velocity')->default(0); // -100 to +100
            $table->decimal('competition_level', 5, 2)->default(0); // Applicants per job
            $table->string('recommendation')->nullable(); // 'pursue', 'consider', 'avoid'
            
            // Metadata
            $table->decimal('confidence_score', 5, 2)->default(0); // Model confidence
            $table->date('prediction_date')->index();
            $table->timestamps();
            
            $table->index(['role_status', 'current_demand_score']);
        });

        // Competitive benchmarks - user vs. market comparison data
        Schema::create('competitive_benchmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('benchmark_category'); // 'skills', 'experience', 'education', 'compensation'
            
            // User metrics
            $table->json('user_data'); // User's current state
            $table->decimal('user_score', 5, 2)->default(0); // 0-100
            
            // Market benchmarks
            $table->json('market_average'); // Market average for comparison
            $table->json('market_top_10'); // Top 10% benchmark
            $table->json('market_top_25'); // Top 25% benchmark
            
            // Gap analysis
            $table->json('gaps_identified'); // What user is missing
            $table->json('strengths_identified'); // Where user excels
            $table->integer('gap_severity')->default(0); // 0-100 urgency
            
            // Recommendations
            $table->json('improvement_actions'); // Specific steps to improve
            $table->integer('estimated_improvement_time')->nullable(); // Days to close gap
            $table->decimal('potential_salary_impact', 12, 2)->nullable(); // Potential earnings increase
            
            // Tracking
            $table->timestamp('benchmarked_at');
            $table->timestamps();
            
            $table->index(['user_id', 'benchmark_category', 'benchmarked_at'], 'competitive_benchmarks_user_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competitive_benchmarks');
        Schema::dropIfExists('role_predictions');
        Schema::dropIfExists('skill_trends');
        Schema::dropIfExists('salary_trends');
        Schema::dropIfExists('user_market_positions');
        Schema::dropIfExists('market_data_snapshots');
    }
};
