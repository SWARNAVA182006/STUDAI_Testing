<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * AI Career Coach - Conversational career advisor with goal tracking
     */
    public function up(): void
    {
        // Career Coach Sessions - Main conversation containers
        Schema::create('career_coach_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title')->nullable();
            $table->enum('session_type', [
                'general_advice',
                'career_planning',
                'skill_development',
                'job_search',
                'interview_prep',
                'salary_negotiation',
                'career_transition',
                'goal_review',
                'weekly_checkin'
            ])->default('general_advice');
            $table->json('context')->nullable(); // Profile snapshot, current goals, etc.
            $table->json('summary')->nullable(); // AI-generated session summary
            $table->json('action_items')->nullable(); // Extracted action items from conversation
            $table->json('key_insights')->nullable(); // Key takeaways
            $table->integer('message_count')->default(0);
            $table->timestamp('last_message_at')->nullable();
            $table->enum('status', ['active', 'completed', 'archived'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'session_type']);
            $table->index('last_message_at');
        });

        // Career Coach Messages - Individual messages in conversations
        Schema::create('career_coach_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('career_coach_sessions')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('role', ['user', 'assistant', 'system']);
            $table->text('content');
            $table->json('metadata')->nullable(); // Suggestions, links, references
            $table->json('voice_data')->nullable(); // For voice interactions
            $table->boolean('is_voice_input')->default(false);
            $table->boolean('is_voice_output')->default(false);
            $table->string('sentiment')->nullable(); // positive, neutral, negative
            $table->json('extracted_entities')->nullable(); // Skills, companies, roles mentioned
            $table->integer('tokens_used')->nullable();
            $table->timestamps();

            $table->index(['session_id', 'created_at']);
            $table->index(['user_id', 'role']);
        });

        // Career Goals - User's career objectives with tracking
        Schema::create('career_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('category', [
                'role_change',      // Get a new role/title
                'salary_increase',  // Increase compensation
                'skill_acquisition', // Learn new skills
                'certification',    // Get certified
                'promotion',        // Get promoted
                'career_pivot',     // Change career field
                'side_project',     // Build something
                'networking',       // Grow network
                'work_life_balance', // Improve balance
                'leadership',       // Leadership development
                'entrepreneurship', // Start a business
                'education',        // Further education
                'other'
            ]);
            $table->enum('timeframe', [
                '1_month',
                '3_months',
                '6_months',
                '1_year',
                '2_years',
                '5_years',
                'ongoing'
            ])->default('6_months');
            $table->date('target_date')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('status', ['not_started', 'in_progress', 'on_hold', 'completed', 'abandoned'])->default('not_started');
            $table->integer('progress_percentage')->default(0);
            $table->json('milestones')->nullable(); // Breakdown of sub-goals
            $table->json('metrics')->nullable(); // Success metrics
            $table->json('obstacles')->nullable(); // Identified challenges
            $table->json('resources')->nullable(); // Helpful resources
            $table->json('ai_recommendations')->nullable(); // AI suggestions
            $table->text('notes')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'category']);
            $table->index(['user_id', 'priority']);
            $table->index('target_date');
        });

        // Goal Progress Updates - Track progress over time
        Schema::create('career_goal_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_id')->constrained('career_goals')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('session_id')->nullable()->constrained('career_coach_sessions')->nullOnDelete();
            $table->text('update_content');
            $table->integer('progress_before')->nullable();
            $table->integer('progress_after')->nullable();
            $table->json('milestones_completed')->nullable();
            $table->json('challenges_faced')->nullable();
            $table->json('next_steps')->nullable();
            $table->json('ai_feedback')->nullable();
            $table->string('mood')->nullable(); // optimistic, neutral, frustrated, etc.
            $table->timestamps();

            $table->index(['goal_id', 'created_at']);
            $table->index('user_id');
        });

        // Weekly Check-ins - Scheduled coaching sessions
        Schema::create('career_coach_checkins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('session_id')->nullable()->constrained('career_coach_sessions')->nullOnDelete();
            $table->date('scheduled_for');
            $table->date('completed_at')->nullable();
            $table->enum('status', ['scheduled', 'completed', 'skipped', 'rescheduled'])->default('scheduled');
            $table->json('goals_reviewed')->nullable(); // Goal IDs reviewed
            $table->json('wins_this_week')->nullable();
            $table->json('challenges_this_week')->nullable();
            $table->json('focus_for_next_week')->nullable();
            $table->json('ai_summary')->nullable();
            $table->integer('overall_sentiment_score')->nullable(); // 1-10
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'scheduled_for']);
            $table->index(['user_id', 'status']);
            $table->unique(['user_id', 'scheduled_for']);
        });

        // Proactive Suggestions - AI-generated suggestions
        Schema::create('career_coach_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('goal_id')->nullable()->constrained('career_goals')->nullOnDelete();
            $table->string('title');
            $table->text('content');
            $table->enum('type', [
                'skill_recommendation',
                'job_opportunity',
                'networking_tip',
                'learning_resource',
                'industry_insight',
                'motivation',
                'deadline_reminder',
                'goal_nudge',
                'celebration'
            ]);
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->json('action_link')->nullable(); // Link to related action
            $table->json('metadata')->nullable();
            $table->boolean('is_read')->default(false);
            $table->boolean('is_dismissed')->default(false);
            $table->boolean('is_acted_upon')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_read']);
            $table->index(['user_id', 'type']);
            $table->index('expires_at');
        });

        // Coach Preferences - User settings for the coach
        Schema::create('career_coach_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('weekly_checkins_enabled')->default(true);
            $table->string('preferred_checkin_day')->default('monday'); // Day of week
            $table->string('preferred_checkin_time')->default('09:00'); // Time in HH:MM
            $table->string('timezone')->default('Asia/Kolkata');
            $table->boolean('proactive_suggestions_enabled')->default(true);
            $table->enum('suggestion_frequency', ['daily', 'weekly', 'occasional'])->default('weekly');
            $table->boolean('voice_enabled')->default(false);
            $table->string('preferred_language')->default('en');
            $table->enum('coaching_style', ['supportive', 'direct', 'analytical', 'motivational'])->default('supportive');
            $table->json('focus_areas')->nullable(); // Categories user wants to focus on
            $table->boolean('email_notifications')->default(true);
            $table->boolean('push_notifications')->default(true);
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('career_coach_preferences');
        Schema::dropIfExists('career_coach_suggestions');
        Schema::dropIfExists('career_coach_checkins');
        Schema::dropIfExists('career_goal_updates');
        Schema::dropIfExists('career_goals');
        Schema::dropIfExists('career_coach_messages');
        Schema::dropIfExists('career_coach_sessions');
    }
};
