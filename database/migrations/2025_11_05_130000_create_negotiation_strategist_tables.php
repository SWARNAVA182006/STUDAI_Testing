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
        // Negotiation Strategies - Stores generated negotiation strategies
        Schema::create('negotiation_strategies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('role');
            $table->string('company_name');
            $table->string('location');
            $table->decimal('offered_salary', 12, 2);
            $table->decimal('current_salary', 12, 2)->nullable();
            $table->integer('years_experience');
            
            // Market Research Data
            $table->decimal('market_median', 12, 2);
            $table->decimal('market_percentile_25', 12, 2);
            $table->decimal('market_percentile_75', 12, 2);
            $table->decimal('market_percentile_90', 12, 2);
            $table->decimal('offered_salary_percentile', 5, 2);
            $table->json('company_salary_data')->nullable(); // Company-specific salary insights
            
            // Optimal Strategy Calculations
            $table->decimal('optimal_ask', 12, 2); // Calculated optimal counter-offer
            $table->decimal('minimum_acceptable', 12, 2); // Walk-away point
            $table->decimal('stretch_goal', 12, 2); // Ambitious but achievable
            $table->decimal('confidence_score', 5, 2); // 0-100, confidence in strategy
            
            // Negotiation Points
            $table->json('strongest_points'); // User's strongest negotiation leverage
            $table->json('value_propositions'); // Unique value user brings
            $table->json('risk_factors'); // Factors that could weaken position
            
            // Timing & Tactics
            $table->enum('recommended_timing', ['immediate', 'within_24h', 'within_48h', 'within_week']);
            $table->text('timing_rationale');
            $table->enum('recommended_tone', ['collaborative', 'confident', 'enthusiastic', 'analytical']);
            $table->json('recommended_tactics'); // Array of tactic IDs/names
            
            // Alternative Benefits
            $table->json('benefits_to_negotiate')->nullable(); // If salary flexibility limited
            $table->json('total_comp_optimization')->nullable(); // Optimize total compensation
            
            // Company Intelligence
            $table->json('company_culture_analysis')->nullable(); // Company culture insights
            $table->text('hiring_manager_perspective')->nullable(); // Predicted manager viewpoint
            $table->enum('company_negotiation_flexibility', ['high', 'medium', 'low', 'unknown'])->default('unknown');
            
            // AI Insights
            $table->text('ai_summary'); // Executive summary
            $table->text('ai_rationale'); // Detailed reasoning
            $table->json('ai_warnings')->nullable(); // Potential pitfalls
            
            $table->enum('status', ['draft', 'active', 'completed', 'archived'])->default('active');
            $table->timestamp('generated_at');
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'created_at']);
            $table->index('status');
            $table->index(['role', 'company_name']);
        });

        // Negotiation Scenarios - Multiple counter-offer scenarios with predicted responses
        Schema::create('negotiation_scenarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('strategy_id')->constrained('negotiation_strategies')->onDelete('cascade');
            $table->string('scenario_name'); // e.g., "Conservative", "Balanced", "Aggressive"
            $table->integer('scenario_order')->default(0);
            
            // Counter-Offer Details
            $table->decimal('counter_offer_amount', 12, 2);
            $table->json('additional_requests')->nullable(); // Equity, bonus, benefits, etc.
            $table->text('counter_offer_justification'); // Why this amount
            
            // Predicted Employer Response
            $table->enum('predicted_response', ['accept', 'counter', 'negotiate', 'reject']);
            $table->decimal('predicted_response_probability', 5, 2); // 0-100%
            $table->decimal('predicted_final_salary', 12, 2)->nullable(); // Likely final outcome
            $table->text('predicted_employer_counter')->nullable(); // What employer might say
            
            // Risk Assessment
            $table->enum('risk_level', ['low', 'medium', 'high', 'very_high']);
            $table->decimal('risk_score', 5, 2); // 0-100
            $table->json('risk_factors'); // What makes this risky
            $table->json('mitigation_strategies')->nullable(); // How to reduce risk
            
            // Scenario Outcomes
            $table->decimal('best_case_outcome', 12, 2);
            $table->decimal('expected_outcome', 12, 2);
            $table->decimal('worst_case_outcome', 12, 2);
            $table->json('success_indicators'); // Signs this scenario is working
            $table->json('failure_indicators'); // Signs to pivot
            
            // Recommendations
            $table->enum('recommendation', ['recommended', 'viable', 'risky', 'not_recommended']);
            $table->text('recommendation_rationale');
            $table->integer('confidence_score')->default(0); // 0-100
            
            $table->timestamps();
            
            // Indexes
            $table->index(['strategy_id', 'scenario_order']);
            $table->index('recommendation');
        });

        // Negotiation Scripts - Professional scripts tailored to company culture
        Schema::create('negotiation_scripts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('strategy_id')->constrained('negotiation_strategies')->onDelete('cascade');
            $table->foreignId('scenario_id')->nullable()->constrained('negotiation_scenarios')->onDelete('set null');
            
            $table->enum('script_type', ['email', 'phone', 'in_person', 'video_call']);
            $table->enum('script_stage', ['initial_response', 'counter_offer', 'follow_up', 'closing']);
            $table->string('script_name'); // e.g., "Enthusiastic Email Response"
            
            // Script Content
            $table->text('subject_line')->nullable(); // For emails
            $table->text('opening'); // How to start
            $table->text('body'); // Main content
            $table->text('closing'); // How to end
            $table->text('full_script'); // Complete script with formatting
            
            // Customization
            $table->json('key_talking_points'); // Main arguments to make
            $table->json('phrases_to_use'); // Effective phrases
            $table->json('phrases_to_avoid'); // What not to say
            $table->json('transition_phrases'); // Connect ideas smoothly
            
            // Tone & Style
            $table->enum('tone', ['professional', 'enthusiastic', 'collaborative', 'confident', 'grateful']);
            $table->json('cultural_adaptations')->nullable(); // Company culture considerations
            $table->text('personality_notes')->nullable(); // How to personalize
            
            // Tactical Elements
            $table->json('anchoring_tactics')->nullable(); // Set expectations
            $table->json('framing_strategies')->nullable(); // How to frame requests
            $table->json('reciprocity_elements')->nullable(); // Give and take
            $table->boolean('includes_deadline')->default(false);
            $table->boolean('includes_alternatives')->default(false);
            
            // Usage Tracking
            $table->integer('effectiveness_rating')->nullable(); // User feedback 1-5
            $table->boolean('was_used')->default(false);
            $table->timestamp('used_at')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['strategy_id', 'script_type', 'script_stage']);
            $table->index('was_used');
        });

        // Negotiation Sessions - Real-time negotiation coaching sessions
        Schema::create('negotiation_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('strategy_id')->constrained('negotiation_strategies')->onDelete('cascade');
            $table->foreignId('scenario_id')->nullable()->constrained('negotiation_scenarios')->onDelete('set null');
            
            $table->enum('session_type', ['preparation', 'live_coaching', 'post_mortem']);
            $table->enum('communication_mode', ['email', 'phone', 'in_person', 'video_call']);
            $table->datetime('session_start');
            $table->datetime('session_end')->nullable();
            $table->integer('duration_minutes')->nullable();
            
            // Session Context
            $table->text('session_goal')->nullable(); // What user wants to achieve
            $table->enum('current_stage', ['initial_offer', 'counter_offer', 'negotiation', 'closing', 'completed']);
            $table->json('session_context')->nullable(); // Any relevant context
            
            // Real-Time Tracking
            $table->json('key_points_discussed')->nullable(); // What was covered
            $table->json('employer_signals')->nullable(); // Employer's responses/body language
            $table->json('user_performance')->nullable(); // How user is doing
            $table->json('ai_interventions')->nullable(); // Times AI suggested course corrections
            
            // Outcomes
            $table->enum('outcome', ['successful', 'pending', 'needs_follow_up', 'unsuccessful', 'user_withdrew'])->nullable();
            $table->decimal('final_agreed_salary', 12, 2)->nullable();
            $table->json('final_agreed_terms')->nullable(); // Complete package
            $table->text('outcome_notes')->nullable();
            
            // Learning & Improvement
            $table->json('what_worked_well')->nullable();
            $table->json('what_to_improve')->nullable();
            $table->json('lessons_learned')->nullable();
            $table->integer('user_satisfaction')->nullable(); // 1-5 rating
            
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'session_start']);
            $table->index(['strategy_id', 'session_type']);
            $table->index('outcome');
        });

        // Negotiation Messages - Real-time conversation flow and AI coaching
        Schema::create('negotiation_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('negotiation_sessions')->onDelete('cascade');
            $table->enum('message_type', ['user_input', 'employer_response', 'ai_suggestion', 'ai_analysis', 'system_note']);
            $table->text('content');
            $table->json('metadata')->nullable(); // Additional context
            
            // For AI Suggestions
            $table->enum('suggestion_category', [
                'response_suggestion',
                'tactic_recommendation',
                'warning',
                'encouragement',
                'data_point',
                'pivot_suggestion',
                'closing_advice'
            ])->nullable();
            
            $table->enum('urgency', ['low', 'medium', 'high', 'critical'])->nullable();
            $table->integer('confidence_score')->nullable(); // 0-100, for AI suggestions
            
            // Conversation Flow
            $table->foreignId('in_response_to')->nullable()->constrained('negotiation_messages')->onDelete('set null');
            $table->json('suggested_responses')->nullable(); // Multiple options
            $table->json('context_analysis')->nullable(); // What AI detected in conversation
            
            // User Feedback
            $table->boolean('was_helpful')->nullable();
            $table->boolean('was_used')->default(false);
            $table->timestamp('used_at')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['session_id', 'created_at']);
            $table->index('message_type');
            $table->index(['suggestion_category', 'urgency']);
        });

        // Negotiation Tactics Library - Reusable tactics with effectiveness tracking
        Schema::create('negotiation_tactics', function (Blueprint $table) {
            $table->id();
            $table->string('tactic_name');
            $table->string('tactic_category'); // anchoring, framing, reciprocity, silence, etc.
            $table->text('description');
            $table->text('when_to_use');
            $table->text('how_to_execute');
            $table->json('example_phrases');
            
            $table->enum('risk_level', ['low', 'medium', 'high']);
            $table->json('best_for_roles')->nullable(); // Which roles this works well for
            $table->json('best_for_industries')->nullable(); // Which industries
            $table->decimal('average_effectiveness', 5, 2)->default(0); // Tracked from usage
            
            $table->integer('times_recommended')->default(0);
            $table->integer('times_used')->default(0);
            $table->integer('times_successful')->default(0);
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Indexes
            $table->index('tactic_category');
            $table->index('risk_level');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('negotiation_messages');
        Schema::dropIfExists('negotiation_sessions');
        Schema::dropIfExists('negotiation_scripts');
        Schema::dropIfExists('negotiation_scenarios');
        Schema::dropIfExists('negotiation_tactics');
        Schema::dropIfExists('negotiation_strategies');
    }
};
