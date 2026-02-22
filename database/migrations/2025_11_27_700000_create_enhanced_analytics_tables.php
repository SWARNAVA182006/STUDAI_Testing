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
        // Job market heatmap data (geographic demand distribution)
        Schema::create('job_market_heatmaps', function (Blueprint $table) {
            $table->id();
            $table->string('location', 100)->index(); // City/State/Country
            $table->string('location_type', 20)->default('city'); // city, state, country
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('industry', 100)->nullable()->index();
            $table->string('job_category', 100)->nullable()->index();
            $table->integer('job_count')->default(0);
            $table->integer('application_count')->default(0);
            $table->decimal('avg_salary', 12, 2)->nullable();
            $table->decimal('median_salary', 12, 2)->nullable();
            $table->decimal('competition_score', 5, 2)->default(0); // 0-100
            $table->decimal('demand_score', 5, 2)->default(0); // 0-100
            $table->decimal('growth_rate', 8, 2)->default(0); // percentage
            $table->date('period_date')->index(); // The date this data represents
            $table->string('period_type', 20)->default('daily'); // daily, weekly, monthly
            $table->json('top_skills')->nullable();
            $table->json('top_companies')->nullable();
            $table->json('salary_ranges')->nullable();
            $table->timestamps();

            $table->unique(['location', 'industry', 'job_category', 'period_date', 'period_type'], 'heatmap_unique');
        });

        // Salary benchmarks for real-time comparisons
        Schema::create('salary_benchmarks', function (Blueprint $table) {
            $table->id();
            $table->string('job_title', 150)->index();
            $table->string('normalized_title', 150)->index(); // Standardized job title
            $table->string('location', 100)->nullable()->index();
            $table->string('industry', 100)->nullable()->index();
            $table->string('experience_level', 30)->nullable(); // entry, mid, senior, lead, executive
            $table->decimal('min_salary', 12, 2);
            $table->decimal('max_salary', 12, 2);
            $table->decimal('median_salary', 12, 2);
            $table->decimal('percentile_25', 12, 2)->nullable();
            $table->decimal('percentile_75', 12, 2)->nullable();
            $table->decimal('percentile_90', 12, 2)->nullable();
            $table->integer('sample_size')->default(0);
            $table->decimal('yoy_change', 8, 2)->nullable(); // Year-over-year % change
            $table->json('benefits_data')->nullable(); // Common benefits
            $table->json('bonus_data')->nullable(); // Bonus/equity info
            $table->string('currency', 3)->default('USD');
            $table->date('period_date')->index();
            $table->timestamps();

            $table->index(['normalized_title', 'location', 'experience_level'], 'salary_bench_idx');
        });

        // Skills demand forecasting data
        Schema::create('skill_demand_forecasts', function (Blueprint $table) {
            $table->id();
            $table->string('skill_name', 100)->index();
            $table->string('skill_category', 50)->nullable()->index(); // technical, soft, domain
            $table->string('industry', 100)->nullable()->index();
            $table->integer('current_demand')->default(0); // Current job postings mentioning skill
            $table->integer('historical_demand_30d')->default(0);
            $table->integer('historical_demand_90d')->default(0);
            $table->integer('historical_demand_180d')->default(0);
            $table->decimal('growth_rate_30d', 8, 2)->default(0);
            $table->decimal('growth_rate_90d', 8, 2)->default(0);
            $table->decimal('predicted_demand_30d', 12, 2)->nullable();
            $table->decimal('predicted_demand_90d', 12, 2)->nullable();
            $table->decimal('predicted_demand_180d', 12, 2)->nullable();
            $table->decimal('confidence_score', 5, 2)->default(0); // 0-100 prediction confidence
            $table->string('trend_direction', 20)->default('stable'); // rising, falling, stable, volatile
            $table->decimal('avg_salary_premium', 8, 2)->nullable(); // Salary boost for having skill
            $table->json('related_skills')->nullable();
            $table->json('complementary_skills')->nullable();
            $table->json('competing_skills')->nullable();
            $table->date('forecast_date')->index();
            $table->timestamps();

            $table->unique(['skill_name', 'industry', 'forecast_date'], 'skill_forecast_unique');
        });

        // Career path nodes for visualization
        Schema::create('career_path_nodes', function (Blueprint $table) {
            $table->id();
            $table->string('job_title', 150)->index();
            $table->string('normalized_title', 150)->index();
            $table->string('industry', 100)->nullable()->index();
            $table->string('level', 30)->default('mid'); // entry, mid, senior, lead, director, executive
            $table->integer('level_rank')->default(3); // 1-6 for ordering
            $table->decimal('avg_salary', 12, 2)->nullable();
            $table->decimal('avg_years_experience', 5, 2)->nullable();
            $table->json('required_skills')->nullable();
            $table->json('common_transitions_to')->nullable(); // Array of next role IDs
            $table->json('common_transitions_from')->nullable(); // Array of previous role IDs
            $table->integer('transition_count')->default(0); // How many people made this transition
            $table->decimal('avg_transition_time', 5, 2)->nullable(); // Years between transitions
            $table->json('certifications')->nullable();
            $table->json('education_requirements')->nullable();
            $table->timestamps();

            $table->unique(['normalized_title', 'industry', 'level'], 'career_node_unique');
        });

        // Career path edges (transitions between nodes)
        Schema::create('career_path_edges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_node_id')->constrained('career_path_nodes')->onDelete('cascade');
            $table->foreignId('to_node_id')->constrained('career_path_nodes')->onDelete('cascade');
            $table->integer('transition_count')->default(0);
            $table->decimal('avg_transition_years', 5, 2)->nullable();
            $table->decimal('salary_increase_percentage', 8, 2)->nullable();
            $table->decimal('success_rate', 5, 2)->nullable(); // How often this transition succeeds
            $table->json('required_skills_gap')->nullable(); // Skills needed for transition
            $table->json('recommended_certifications')->nullable();
            $table->timestamps();

            $table->unique(['from_node_id', 'to_node_id'], 'career_edge_unique');
        });

        // Application funnel analytics
        Schema::create('application_funnels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employer_id')->nullable()->index();
            $table->unsignedBigInteger('job_id')->nullable()->index();
            $table->string('industry', 100)->nullable()->index();
            $table->string('job_category', 100)->nullable()->index();
            $table->integer('views_count')->default(0);
            $table->integer('applications_count')->default(0);
            $table->integer('screening_count')->default(0);
            $table->integer('interview_count')->default(0);
            $table->integer('offer_count')->default(0);
            $table->integer('hired_count')->default(0);
            $table->integer('rejected_count')->default(0);
            $table->integer('withdrawn_count')->default(0);
            $table->decimal('view_to_apply_rate', 5, 2)->default(0);
            $table->decimal('apply_to_screen_rate', 5, 2)->default(0);
            $table->decimal('screen_to_interview_rate', 5, 2)->default(0);
            $table->decimal('interview_to_offer_rate', 5, 2)->default(0);
            $table->decimal('offer_to_hire_rate', 5, 2)->default(0);
            $table->decimal('overall_conversion_rate', 5, 2)->default(0);
            $table->date('period_date')->index();
            $table->string('period_type', 20)->default('daily'); // daily, weekly, monthly
            $table->timestamps();

            $table->index(['employer_id', 'period_date'], 'funnel_employer_date_idx');
        });

        // Time-to-hire metrics
        Schema::create('time_to_hire_metrics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employer_id')->nullable()->index();
            $table->unsignedBigInteger('job_id')->nullable()->index();
            $table->string('industry', 100)->nullable()->index();
            $table->string('job_category', 100)->nullable()->index();
            $table->string('experience_level', 30)->nullable();
            $table->decimal('avg_days_to_first_application', 8, 2)->nullable();
            $table->decimal('avg_days_to_first_interview', 8, 2)->nullable();
            $table->decimal('avg_days_to_offer', 8, 2)->nullable();
            $table->decimal('avg_days_to_hire', 8, 2)->nullable();
            $table->decimal('median_days_to_hire', 8, 2)->nullable();
            $table->decimal('min_days_to_hire', 8, 2)->nullable();
            $table->decimal('max_days_to_hire', 8, 2)->nullable();
            $table->integer('sample_size')->default(0);
            $table->json('stage_breakdown')->nullable(); // Time at each stage
            $table->date('period_date')->index();
            $table->string('period_type', 20)->default('monthly');
            $table->timestamps();

            $table->index(['employer_id', 'period_date'], 'tth_employer_date_idx');
        });

        // Source attribution tracking
        Schema::create('source_attributions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employer_id')->nullable()->index();
            $table->string('source_name', 100)->index(); // LinkedIn, Indeed, Direct, Referral, etc.
            $table->string('source_category', 50)->index(); // job_board, social, referral, direct, email
            $table->string('utm_source', 100)->nullable();
            $table->string('utm_medium', 100)->nullable();
            $table->string('utm_campaign', 100)->nullable();
            $table->integer('views_count')->default(0);
            $table->integer('applications_count')->default(0);
            $table->integer('interviews_count')->default(0);
            $table->integer('hires_count')->default(0);
            $table->decimal('cost_per_click', 12, 2)->nullable();
            $table->decimal('cost_per_application', 12, 2)->nullable();
            $table->decimal('cost_per_hire', 12, 2)->nullable();
            $table->decimal('total_spend', 12, 2)->nullable();
            $table->decimal('quality_score', 5, 2)->default(0); // 0-100 based on hire rate
            $table->decimal('time_to_hire_avg', 8, 2)->nullable();
            $table->date('period_date')->index();
            $table->string('period_type', 20)->default('daily');
            $table->timestamps();

            $table->index(['employer_id', 'source_name', 'period_date'], 'source_attr_idx');
        });

        // Competitor salary data (anonymized/aggregated)
        Schema::create('competitor_salary_data', function (Blueprint $table) {
            $table->id();
            $table->string('job_title', 150)->index();
            $table->string('normalized_title', 150)->index();
            $table->string('industry', 100)->index();
            $table->string('location', 100)->nullable()->index();
            $table->string('company_size', 30)->nullable(); // startup, small, medium, large, enterprise
            $table->string('company_type', 30)->nullable(); // public, private, nonprofit
            $table->decimal('avg_salary', 12, 2);
            $table->decimal('median_salary', 12, 2);
            $table->decimal('percentile_25', 12, 2)->nullable();
            $table->decimal('percentile_75', 12, 2)->nullable();
            $table->decimal('market_rate', 12, 2)->nullable(); // Overall market average
            $table->decimal('deviation_from_market', 8, 2)->nullable(); // % above/below market
            $table->integer('sample_size')->default(0);
            $table->json('benefits_comparison')->nullable();
            $table->json('equity_data')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->date('data_date')->index();
            $table->timestamps();

            $table->index(['normalized_title', 'industry', 'location'], 'competitor_salary_idx');
        });

        // Analytics dashboard preferences (per user)
        Schema::create('analytics_dashboard_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('dashboard_type', 50)->default('default'); // default, employer, job_seeker
            $table->json('visible_widgets')->nullable();
            $table->json('widget_order')->nullable();
            $table->json('filters')->nullable();
            $table->json('date_range_preset')->nullable();
            $table->json('comparison_settings')->nullable();
            $table->boolean('show_predictions')->default(true);
            $table->boolean('show_benchmarks')->default(true);
            $table->string('chart_theme', 30)->default('default');
            $table->timestamps();

            $table->unique(['user_id', 'dashboard_type'], 'user_dashboard_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_dashboard_preferences');
        Schema::dropIfExists('competitor_salary_data');
        Schema::dropIfExists('source_attributions');
        Schema::dropIfExists('time_to_hire_metrics');
        Schema::dropIfExists('application_funnels');
        Schema::dropIfExists('career_path_edges');
        Schema::dropIfExists('career_path_nodes');
        Schema::dropIfExists('skill_demand_forecasts');
        Schema::dropIfExists('salary_benchmarks');
        Schema::dropIfExists('job_market_heatmaps');
    }
};
