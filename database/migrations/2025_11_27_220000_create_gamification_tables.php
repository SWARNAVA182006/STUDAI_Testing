<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Complete Gamification System for StudAI Career:
     * - Points/XP system with levels
     * - Achievements with tiers (bronze/silver/gold/platinum)
     * - Badges and certifications
     * - Application streaks
     * - Leaderboards (industry-based, opt-in)
     * - Rewards marketplace
     * - Daily challenges
     * - Seasonal events
     */
    public function up(): void
    {
        // User Gamification Profile - Core stats and settings
        Schema::create('user_gamification_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Points and Level
            $table->unsignedBigInteger('total_points')->default(0);
            $table->unsignedBigInteger('available_points')->default(0); // Spendable in rewards marketplace
            $table->unsignedInteger('level')->default(1);
            $table->unsignedBigInteger('xp_current')->default(0); // Current XP in level
            $table->unsignedBigInteger('xp_required')->default(100); // XP needed for next level
            
            // Streaks
            $table->unsignedInteger('current_streak')->default(0);
            $table->unsignedInteger('longest_streak')->default(0);
            $table->date('last_activity_date')->nullable();
            $table->unsignedInteger('streak_freeze_count')->default(0); // Streak protections available
            
            // Statistics
            $table->unsignedInteger('achievements_unlocked')->default(0);
            $table->unsignedInteger('badges_earned')->default(0);
            $table->unsignedInteger('challenges_completed')->default(0);
            $table->unsignedInteger('rewards_redeemed')->default(0);
            
            // Leaderboard Settings
            $table->boolean('show_on_leaderboard')->default(true);
            $table->string('leaderboard_display_name')->nullable(); // Anonymous name option
            $table->string('primary_industry')->nullable();
            
            // Multipliers
            $table->decimal('xp_multiplier', 4, 2)->default(1.00);
            $table->timestamp('multiplier_expires_at')->nullable();
            
            $table->timestamps();
            
            $table->unique('user_id');
            $table->index(['total_points', 'level']);
            $table->index('primary_industry');
        });

        // Achievement Definitions
        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description');
            $table->string('category'); // profile, applications, skills, networking, marketplace, career
            $table->string('icon')->nullable();
            $table->string('tier')->default('bronze'); // bronze, silver, gold, platinum, diamond
            
            // Unlock Requirements
            $table->string('trigger_type'); // count, milestone, special, daily, weekly
            $table->string('trigger_action'); // profile_complete, applications_sent, skills_validated, etc.
            $table->unsignedInteger('trigger_count')->default(1);
            $table->json('trigger_conditions')->nullable(); // Additional conditions
            
            // Rewards
            $table->unsignedInteger('points_reward')->default(0);
            $table->unsignedInteger('xp_reward')->default(0);
            $table->string('badge_reward')->nullable(); // Optional badge unlock
            
            // Display
            $table->boolean('is_secret')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            
            $table->timestamps();
            
            $table->index(['category', 'tier']);
            $table->index('trigger_action');
        });

        // User Achievements (Unlocked)
        Schema::create('user_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('achievement_id')->constrained()->onDelete('cascade');
            
            $table->timestamp('unlocked_at');
            $table->unsignedInteger('progress')->default(0); // For progressive achievements
            $table->unsignedInteger('target')->default(1);
            $table->boolean('is_completed')->default(false);
            $table->boolean('is_claimed')->default(false); // Has user claimed the reward
            $table->timestamp('claimed_at')->nullable();
            
            $table->timestamps();
            
            $table->unique(['user_id', 'achievement_id']);
            $table->index(['user_id', 'is_completed']);
        });

        // Badges (Visual Recognition)
        Schema::create('gamification_badges', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description');
            $table->string('category'); // skill, career, social, special, seasonal
            $table->string('icon');
            $table->string('color')->default('#6366f1');
            
            // Rarity
            $table->string('rarity')->default('common'); // common, uncommon, rare, epic, legendary
            
            // How to earn
            $table->string('earn_type'); // achievement, purchase, event, admin, skill_test
            $table->unsignedBigInteger('earn_reference_id')->nullable(); // Achievement ID, etc.
            $table->unsignedInteger('purchase_cost')->nullable(); // If purchasable
            
            // Display
            $table->boolean('is_displayable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamp('available_from')->nullable();
            $table->timestamp('available_until')->nullable();
            
            $table->timestamps();
            
            $table->index(['category', 'rarity']);
        });

        // User Badges
        Schema::create('user_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('badge_id')->constrained('gamification_badges')->onDelete('cascade');
            
            $table->timestamp('earned_at');
            $table->boolean('is_featured')->default(false); // Display on profile
            $table->unsignedInteger('display_order')->default(0);
            
            $table->timestamps();
            
            $table->unique(['user_id', 'badge_id']);
            $table->index(['user_id', 'is_featured']);
        });

        // Daily Challenges
        Schema::create('daily_challenges', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description');
            $table->string('category');
            $table->string('difficulty')->default('easy'); // easy, medium, hard
            
            // Requirements
            $table->string('action_type'); // apply_job, complete_profile, skill_test, etc.
            $table->unsignedInteger('action_count')->default(1);
            $table->json('action_conditions')->nullable();
            
            // Rewards
            $table->unsignedInteger('points_reward')->default(10);
            $table->unsignedInteger('xp_reward')->default(25);
            $table->unsignedInteger('streak_bonus')->default(0); // Extra for streak
            
            // Scheduling
            $table->string('day_of_week')->nullable(); // For specific day challenges
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('weight')->default(1); // Selection probability
            
            $table->timestamps();
        });

        // User Daily Challenge Progress
        Schema::create('user_daily_challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('challenge_id')->constrained('daily_challenges')->onDelete('cascade');
            
            $table->date('challenge_date');
            $table->unsignedInteger('progress')->default(0);
            $table->unsignedInteger('target')->default(1);
            $table->boolean('is_completed')->default(false);
            $table->boolean('is_claimed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('claimed_at')->nullable();
            
            $table->timestamps();
            
            $table->unique(['user_id', 'challenge_id', 'challenge_date']);
            $table->index(['user_id', 'challenge_date']);
        });

        // Leaderboards
        Schema::create('leaderboards', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type'); // global, industry, weekly, monthly, seasonal
            $table->string('industry')->nullable();
            $table->string('metric'); // total_points, weekly_xp, achievements, streak
            
            // Time Period
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            
            // Rewards
            $table->json('rank_rewards')->nullable(); // Rewards by rank position
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['type', 'is_active']);
            $table->index('industry');
        });

        // Leaderboard Entries (Cached rankings)
        Schema::create('leaderboard_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leaderboard_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            $table->unsignedInteger('rank')->default(0);
            $table->unsignedBigInteger('score')->default(0);
            $table->unsignedInteger('previous_rank')->nullable();
            $table->integer('rank_change')->default(0);
            
            $table->timestamps();
            
            $table->unique(['leaderboard_id', 'user_id']);
            $table->index(['leaderboard_id', 'rank']);
        });

        // Rewards Marketplace
        Schema::create('rewards', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description');
            $table->string('category'); // premium_feature, badge, boost, physical, partner
            $table->string('type'); // one_time, subscription, consumable
            
            // Cost
            $table->unsignedInteger('points_cost')->default(0);
            $table->unsignedInteger('level_required')->default(1);
            
            // What you get
            $table->string('reward_type'); // feature_unlock, badge, xp_boost, streak_freeze, profile_boost, etc.
            $table->json('reward_data')->nullable();
            $table->unsignedInteger('duration_days')->nullable(); // For time-limited rewards
            
            // Stock
            $table->unsignedInteger('stock')->nullable(); // Null = unlimited
            $table->unsignedInteger('redeemed_count')->default(0);
            $table->unsignedInteger('per_user_limit')->nullable();
            
            // Display
            $table->string('image')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('available_from')->nullable();
            $table->timestamp('available_until')->nullable();
            
            $table->timestamps();
            
            $table->index(['category', 'is_active']);
            $table->index(['is_featured', 'is_active']);
        });

        // User Redeemed Rewards
        Schema::create('user_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('reward_id')->constrained()->onDelete('cascade');
            
            $table->unsignedInteger('points_spent')->default(0);
            $table->string('status')->default('active'); // active, used, expired
            $table->timestamp('redeemed_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'reward_id']);
        });

        // Points Transactions (Audit Log)
        Schema::create('points_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            $table->string('type'); // earned, spent, bonus, refund, expired
            $table->integer('points'); // Can be negative for spending
            $table->unsignedBigInteger('balance_after')->default(0);
            
            $table->string('source'); // achievement, challenge, reward, streak, referral, admin
            $table->string('source_type')->nullable(); // Model type
            $table->unsignedBigInteger('source_id')->nullable();
            
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
            $table->index(['type', 'source']);
        });

        // XP Transactions
        Schema::create('xp_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            $table->unsignedInteger('xp_earned')->default(0);
            $table->unsignedInteger('level_before');
            $table->unsignedInteger('level_after');
            $table->boolean('leveled_up')->default(false);
            
            $table->string('source');
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            
            $table->decimal('multiplier_applied', 4, 2)->default(1.00);
            $table->text('description')->nullable();
            
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
        });

        // Seasonal Events
        Schema::create('seasonal_events', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description');
            $table->string('theme'); // career_sprint, skill_summer, networking_november, etc.
            
            // Timing
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            
            // Bonuses
            $table->decimal('xp_multiplier', 4, 2)->default(1.00);
            $table->decimal('points_multiplier', 4, 2)->default(1.00);
            
            // Special Content
            $table->json('exclusive_badges')->nullable();
            $table->json('exclusive_challenges')->nullable();
            $table->json('exclusive_rewards')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['starts_at', 'ends_at']);
        });

        // User Event Participation
        Schema::create('user_event_participation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('event_id')->constrained('seasonal_events')->onDelete('cascade');
            
            $table->unsignedInteger('event_points')->default(0);
            $table->unsignedInteger('event_xp')->default(0);
            $table->unsignedInteger('tasks_completed')->default(0);
            $table->json('rewards_claimed')->nullable();
            
            $table->timestamps();
            
            $table->unique(['user_id', 'event_id']);
        });

        // Activity Log (For tracking actions)
        Schema::create('gamification_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            $table->string('action'); // login, apply_job, complete_profile, pass_test, etc.
            $table->string('action_category'); // auth, jobs, profile, skills, networking
            $table->string('actionable_type')->nullable();
            $table->unsignedBigInteger('actionable_id')->nullable();
            
            $table->unsignedInteger('points_earned')->default(0);
            $table->unsignedInteger('xp_earned')->default(0);
            
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'action', 'created_at']);
            $table->index(['action', 'created_at']);
        });

        // Referral Bonuses (Gamification integration)
        Schema::create('gamification_referral_bonuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('referred_id')->constrained('users')->onDelete('cascade');
            
            $table->string('milestone'); // signup, profile_complete, first_application, first_interview
            $table->unsignedInteger('points_awarded')->default(0);
            $table->unsignedInteger('xp_awarded')->default(0);
            
            $table->boolean('is_claimed')->default(false);
            $table->timestamp('claimed_at')->nullable();
            
            $table->timestamps();
            
            $table->unique(['referrer_id', 'referred_id', 'milestone'], 'gam_ref_bonus_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gamification_referral_bonuses');
        Schema::dropIfExists('gamification_activities');
        Schema::dropIfExists('user_event_participation');
        Schema::dropIfExists('seasonal_events');
        Schema::dropIfExists('xp_transactions');
        Schema::dropIfExists('points_transactions');
        Schema::dropIfExists('user_rewards');
        Schema::dropIfExists('rewards');
        Schema::dropIfExists('leaderboard_entries');
        Schema::dropIfExists('leaderboards');
        Schema::dropIfExists('user_daily_challenges');
        Schema::dropIfExists('daily_challenges');
        Schema::dropIfExists('user_badges');
        Schema::dropIfExists('gamification_badges');
        Schema::dropIfExists('user_achievements');
        Schema::dropIfExists('achievements');
        Schema::dropIfExists('user_gamification_profiles');
    }
};
