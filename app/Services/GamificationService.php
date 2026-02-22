<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Achievement;
use App\Models\DailyChallenge;
use App\Models\GamificationActivity;
use App\Models\GamificationBadge;
use App\Models\GamificationReferralBonus;
use App\Models\Leaderboard;
use App\Models\LeaderboardEntry;
use App\Models\PointsTransaction;
use App\Models\Reward;
use App\Models\SeasonalEvent;
use App\Models\User;
use App\Models\UserAchievement;
use App\Models\UserBadge;
use App\Models\UserDailyChallenge;
use App\Models\UserEventParticipation;
use App\Models\UserGamificationProfile;
use App\Models\UserReward;
use App\Models\XpTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GamificationService
{
    // ─────────────────────────────────────────────────────────────────────────────
    // Profile Management
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Get or create gamification profile for user.
     */
    public function getProfile(User $user): UserGamificationProfile
    {
        return UserGamificationProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'total_points' => 0,
                'available_points' => 0,
                'level' => 1,
                'xp_current' => 0,
                'xp_required' => 100,
                'current_streak' => 0,
                'longest_streak' => 0,
            ]
        );
    }

    /**
     * Get profile statistics for dashboard.
     */
    public function getProfileStats(User $user): array
    {
        $profile = $this->getProfile($user);

        return [
            'profile' => $profile,
            'level' => $profile->level,
            'total_points' => $profile->total_points,
            'available_points' => $profile->available_points,
            'xp_current' => $profile->xp_current,
            'xp_required' => $profile->xp_required,
            'xp_progress' => $profile->level_progress,
            'current_streak' => $profile->current_streak,
            'longest_streak' => $profile->longest_streak,
            'achievements_count' => $profile->achievements_unlocked,
            'badges_count' => $profile->badges_earned,
            'rank' => $this->getUserGlobalRank($user),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Activity Tracking & Points/XP Awarding
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Track an activity and award points/XP.
     */
    public function trackActivity(
        User $user,
        string $action,
        ?string $actionableType = null,
        ?int $actionableId = null,
        array $metadata = []
    ): array {
        $rewards = GamificationActivity::getRewardsForAction($action);
        $profile = $this->getProfile($user);

        // Check for daily limits on certain actions
        if (!$this->canPerformAction($user, $action)) {
            return [
                'success' => false,
                'message' => 'Daily limit reached for this action',
                'points' => 0,
                'xp' => 0,
            ];
        }

        // Apply multipliers
        $pointsMultiplier = $this->getActivePointsMultiplier($user);
        $xpMultiplier = $profile->active_multiplier;

        $pointsEarned = (int) round($rewards['points'] * $pointsMultiplier);
        $xpEarned = (int) round($rewards['xp'] * $xpMultiplier);

        DB::beginTransaction();

        try {
            // Log the activity
            $activity = GamificationActivity::create([
                'user_id' => $user->id,
                'action' => $action,
                'action_category' => $rewards['category'],
                'actionable_type' => $actionableType,
                'actionable_id' => $actionableId,
                'points_earned' => $pointsEarned,
                'xp_earned' => $xpEarned,
                'metadata' => $metadata,
            ]);

            // Award points
            if ($pointsEarned > 0) {
                $this->awardPoints($user, $pointsEarned, 'earned', 'activity', $activity->id, $action);
            }

            // Award XP
            $leveledUp = false;
            if ($xpEarned > 0) {
                $leveledUp = $this->awardXp($user, $xpEarned, 'activity', $activity->id, $xpMultiplier);
            }

            // Update streak
            $this->updateStreak($user);

            // Check for achievements
            $unlockedAchievements = $this->checkAchievements($user, $action);

            // Update daily challenges
            $this->updateDailyChallenges($user, $action);

            // Update event participation
            $this->updateEventParticipation($user, $pointsEarned, $xpEarned);

            DB::commit();

            return [
                'success' => true,
                'points' => $pointsEarned,
                'xp' => $xpEarned,
                'leveled_up' => $leveledUp,
                'new_level' => $profile->fresh()->level,
                'achievements_unlocked' => $unlockedAchievements,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gamification tracking failed', [
                'user_id' => $user->id,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to track activity',
                'points' => 0,
                'xp' => 0,
            ];
        }
    }

    /**
     * Award points to user.
     */
    public function awardPoints(
        User $user,
        int $points,
        string $type = 'earned',
        string $source = 'manual',
        ?int $sourceId = null,
        ?string $description = null
    ): void {
        $profile = $this->getProfile($user);

        $profile->increment('total_points', $points);
        $profile->increment('available_points', $points);

        PointsTransaction::create([
            'user_id' => $user->id,
            'type' => $type,
            'points' => $points,
            'balance_after' => $profile->fresh()->available_points,
            'source' => $source,
            'source_type' => null,
            'source_id' => $sourceId,
            'description' => $description,
        ]);
    }

    /**
     * Spend points (for rewards).
     */
    public function spendPoints(
        User $user,
        int $points,
        string $source = 'reward',
        ?int $sourceId = null,
        ?string $description = null
    ): bool {
        $profile = $this->getProfile($user);

        if ($profile->available_points < $points) {
            return false;
        }

        $profile->decrement('available_points', $points);

        PointsTransaction::create([
            'user_id' => $user->id,
            'type' => 'spent',
            'points' => -$points,
            'balance_after' => $profile->fresh()->available_points,
            'source' => $source,
            'source_type' => null,
            'source_id' => $sourceId,
            'description' => $description,
        ]);

        return true;
    }

    /**
     * Award XP and handle level ups.
     */
    public function awardXp(
        User $user,
        int $xp,
        string $source = 'manual',
        ?int $sourceId = null,
        float $multiplier = 1.0
    ): bool {
        $profile = $this->getProfile($user);
        $levelBefore = $profile->level;

        $profile->xp_current += $xp;

        // Handle level up(s)
        $leveledUp = false;
        while ($profile->xp_current >= $profile->xp_required) {
            $profile->xp_current -= $profile->xp_required;
            $profile->level++;
            $profile->xp_required = $profile->calculateXpForLevel($profile->level);
            $leveledUp = true;
        }

        $profile->save();

        // Log XP transaction
        XpTransaction::create([
            'user_id' => $user->id,
            'xp_earned' => $xp,
            'level_before' => $levelBefore,
            'level_after' => $profile->level,
            'leveled_up' => $leveledUp,
            'source' => $source,
            'source_id' => $sourceId,
            'multiplier_applied' => $multiplier,
        ]);

        // Award level up bonus
        if ($leveledUp) {
            $levelUpBonus = ($profile->level - $levelBefore) * 25;
            $this->awardPoints($user, $levelUpBonus, 'bonus', 'level_up', null, "Leveled up to {$profile->level}");
        }

        return $leveledUp;
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Streak Management
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Update user's activity streak.
     */
    public function updateStreak(User $user): array
    {
        $profile = $this->getProfile($user);
        $today = today();
        $lastActivity = $profile->last_activity_date;

        $streakBroken = false;
        $streakIncreased = false;

        if (!$lastActivity) {
            // First activity ever
            $profile->current_streak = 1;
            $profile->longest_streak = 1;
            $streakIncreased = true;
        } elseif ($lastActivity->isToday()) {
            // Already logged today, no change
        } elseif ($lastActivity->isYesterday()) {
            // Continue streak
            $profile->current_streak++;
            $streakIncreased = true;
            if ($profile->current_streak > $profile->longest_streak) {
                $profile->longest_streak = $profile->current_streak;
            }
        } else {
            // Streak broken - check for streak freeze
            if ($profile->streak_freeze_count > 0 && $lastActivity->diffInDays($today) === 2) {
                $profile->streak_freeze_count--;
            } else {
                $profile->current_streak = 1;
                $streakBroken = true;
            }
        }

        $profile->last_activity_date = $today;
        $profile->save();

        // Award streak bonus
        if ($streakIncreased && $profile->current_streak > 1) {
            $streakBonus = min($profile->current_streak * 5, 50); // Max 50 points per day
            $this->awardPoints(
                $user,
                $streakBonus,
                'bonus',
                'streak',
                null,
                "{$profile->current_streak} day streak bonus"
            );
        }

        return [
            'current_streak' => $profile->current_streak,
            'longest_streak' => $profile->longest_streak,
            'streak_broken' => $streakBroken,
            'streak_increased' => $streakIncreased,
        ];
    }

    /**
     * Use a streak freeze to protect streak.
     */
    public function useStreakFreeze(User $user): bool
    {
        $profile = $this->getProfile($user);

        if ($profile->streak_freeze_count <= 0) {
            return false;
        }

        $profile->decrement('streak_freeze_count');
        
        return true;
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Achievement System
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Check and unlock achievements for user.
     */
    public function checkAchievements(User $user, string $action): array
    {
        $unlockedAchievements = [];

        // Get achievements triggered by this action
        $achievements = Achievement::active()
            ->where('trigger_action', $action)
            ->get();

        foreach ($achievements as $achievement) {
            if ($this->canUnlockAchievement($user, $achievement)) {
                $unlocked = $this->unlockAchievement($user, $achievement);
                if ($unlocked) {
                    $unlockedAchievements[] = $achievement;
                }
            }
        }

        // Check progressive achievements
        $this->updateAchievementProgress($user, $action);

        return $unlockedAchievements;
    }

    /**
     * Check if user can unlock an achievement.
     */
    protected function canUnlockAchievement(User $user, Achievement $achievement): bool
    {
        // Already unlocked?
        $existing = UserAchievement::where('user_id', $user->id)
            ->where('achievement_id', $achievement->id)
            ->where('is_completed', true)
            ->exists();

        if ($existing) {
            return false;
        }

        // Check trigger count
        $actionCount = GamificationActivity::forUser($user->id)
            ->byAction($achievement->trigger_action)
            ->count();

        if ($actionCount < $achievement->trigger_count) {
            return false;
        }

        // Check additional conditions
        if ($achievement->trigger_conditions) {
            if (!$this->checkAchievementConditions($user, $achievement->trigger_conditions)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Unlock an achievement for user.
     */
    public function unlockAchievement(User $user, Achievement $achievement): bool
    {
        $userAchievement = UserAchievement::firstOrCreate(
            [
                'user_id' => $user->id,
                'achievement_id' => $achievement->id,
            ],
            [
                'progress' => $achievement->trigger_count,
                'target' => $achievement->trigger_count,
                'is_completed' => false,
            ]
        );

        if ($userAchievement->is_completed) {
            return false;
        }

        $userAchievement->markCompleted();

        // Update profile stats
        $profile = $this->getProfile($user);
        $profile->increment('achievements_unlocked');

        // Award achievement rewards
        if ($achievement->points_reward > 0) {
            $this->awardPoints(
                $user,
                $achievement->total_reward_points,
                'earned',
                'achievement',
                $achievement->id,
                "Achievement: {$achievement->name}"
            );
        }

        if ($achievement->xp_reward > 0) {
            $this->awardXp($user, $achievement->xp_reward, 'achievement', $achievement->id);
        }

        // Award badge if specified
        if ($achievement->badge_reward) {
            $badge = GamificationBadge::where('slug', $achievement->badge_reward)->first();
            if ($badge) {
                $this->awardBadge($user, $badge);
            }
        }

        return true;
    }

    /**
     * Update progress for progressive achievements.
     */
    protected function updateAchievementProgress(User $user, string $action): void
    {
        $achievements = Achievement::active()
            ->where('trigger_action', $action)
            ->where('trigger_type', 'count')
            ->get();

        foreach ($achievements as $achievement) {
            $userAchievement = UserAchievement::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'achievement_id' => $achievement->id,
                ],
                [
                    'progress' => 0,
                    'target' => $achievement->trigger_count,
                    'is_completed' => false,
                ]
            );

            if (!$userAchievement->is_completed) {
                $userAchievement->incrementProgress();
            }
        }
    }

    /**
     * Check custom achievement conditions.
     */
    protected function checkAchievementConditions(User $user, array $conditions): bool
    {
        foreach ($conditions as $key => $value) {
            switch ($key) {
                case 'min_level':
                    if ($this->getProfile($user)->level < $value) {
                        return false;
                    }
                    break;
                case 'min_streak':
                    if ($this->getProfile($user)->current_streak < $value) {
                        return false;
                    }
                    break;
                // Add more condition types as needed
            }
        }

        return true;
    }

    /**
     * Claim rewards for completed achievement.
     */
    public function claimAchievementReward(User $user, int $achievementId): array
    {
        $userAchievement = UserAchievement::where('user_id', $user->id)
            ->where('achievement_id', $achievementId)
            ->first();

        if (!$userAchievement || !$userAchievement->canClaim()) {
            return ['success' => false, 'message' => 'Cannot claim this achievement'];
        }

        $userAchievement->claim();

        return ['success' => true, 'message' => 'Achievement reward claimed!'];
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Badge System
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Award a badge to user.
     */
    public function awardBadge(User $user, GamificationBadge $badge): bool
    {
        // Check if already owned
        if ($badge->isOwnedBy($user)) {
            return false;
        }

        UserBadge::create([
            'user_id' => $user->id,
            'badge_id' => $badge->id,
            'earned_at' => now(),
        ]);

        $profile = $this->getProfile($user);
        $profile->increment('badges_earned');

        return true;
    }

    /**
     * Purchase a badge with points.
     */
    public function purchaseBadge(User $user, GamificationBadge $badge): array
    {
        if (!$badge->isPurchasable()) {
            return ['success' => false, 'message' => 'This badge cannot be purchased'];
        }

        if ($badge->isOwnedBy($user)) {
            return ['success' => false, 'message' => 'You already own this badge'];
        }

        if (!$badge->isAvailable()) {
            return ['success' => false, 'message' => 'This badge is not currently available'];
        }

        $profile = $this->getProfile($user);
        if ($profile->available_points < $badge->purchase_cost) {
            return ['success' => false, 'message' => 'Not enough points'];
        }

        DB::transaction(function () use ($user, $badge) {
            $this->spendPoints($user, $badge->purchase_cost, 'badge_purchase', $badge->id);
            $this->awardBadge($user, $badge);
        });

        return ['success' => true, 'message' => 'Badge purchased successfully!'];
    }

    /**
     * Get user's badges.
     */
    public function getUserBadges(User $user, bool $featuredOnly = false): \Illuminate\Support\Collection
    {
        $query = UserBadge::forUser($user->id)
            ->with('badge')
            ->ordered();

        if ($featuredOnly) {
            $query->featured();
        }

        return $query->get();
    }

    /**
     * Toggle badge featured status.
     */
    public function toggleBadgeFeatured(User $user, int $badgeId): bool
    {
        $userBadge = UserBadge::where('user_id', $user->id)
            ->where('badge_id', $badgeId)
            ->first();

        if (!$userBadge) {
            return false;
        }

        $userBadge->toggleFeatured();
        
        return true;
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Daily Challenges
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Get today's challenges for user.
     */
    public function getTodayChallenges(User $user): \Illuminate\Support\Collection
    {
        // Get or create today's challenges
        $existingChallenges = UserDailyChallenge::forUser($user->id)
            ->forToday()
            ->with('challenge')
            ->get();

        if ($existingChallenges->count() >= 3) {
            return $existingChallenges;
        }

        // Select new challenges for today
        $challenges = DailyChallenge::selectRandomForToday(3 - $existingChallenges->count());

        foreach ($challenges as $challenge) {
            UserDailyChallenge::create([
                'user_id' => $user->id,
                'challenge_id' => $challenge->id,
                'challenge_date' => today(),
                'target' => $challenge->action_count,
            ]);
        }

        return UserDailyChallenge::forUser($user->id)
            ->forToday()
            ->with('challenge')
            ->get();
    }

    /**
     * Update daily challenge progress.
     */
    protected function updateDailyChallenges(User $user, string $action): void
    {
        $userChallenges = UserDailyChallenge::forUser($user->id)
            ->forToday()
            ->active()
            ->with('challenge')
            ->get();

        foreach ($userChallenges as $userChallenge) {
            if ($userChallenge->challenge->action_type === $action) {
                $userChallenge->incrementProgress();

                if ($userChallenge->is_completed) {
                    $profile = $this->getProfile($user);
                    $profile->increment('challenges_completed');
                }
            }
        }
    }

    /**
     * Claim daily challenge reward.
     */
    public function claimDailyChallengeReward(User $user, int $userChallengeId): array
    {
        $userChallenge = UserDailyChallenge::forUser($user->id)
            ->where('id', $userChallengeId)
            ->first();

        if (!$userChallenge || !$userChallenge->canClaim()) {
            return ['success' => false, 'message' => 'Cannot claim this challenge'];
        }

        $challenge = $userChallenge->challenge;
        $profile = $this->getProfile($user);
        $rewards = $challenge->getTotalRewardsForStreak($profile->current_streak);

        DB::transaction(function () use ($user, $userChallenge, $rewards, $challenge) {
            $userChallenge->claim();
            
            if ($rewards['points'] > 0) {
                $this->awardPoints(
                    $user,
                    $rewards['points'],
                    'earned',
                    'challenge',
                    $challenge->id,
                    "Daily Challenge: {$challenge->name}"
                );
            }

            if ($rewards['xp'] > 0) {
                $this->awardXp($user, $rewards['xp'], 'challenge', $challenge->id);
            }
        });

        return [
            'success' => true,
            'points' => $rewards['points'],
            'xp' => $rewards['xp'],
        ];
    }

    /**
     * Claim daily login reward.
     */
    public function claimDailyLoginReward(User $user): array
    {
        $profile = $this->getProfile($user);
        
        // Check if already claimed today
        if ($profile->last_activity_date && $profile->last_activity_date->isToday()) {
            return ['success' => false, 'message' => 'Daily reward already claimed today'];
        }

        // Calculate streak-based bonus
        $basePoints = 10;
        $baseXp = 25;
        $streakBonus = min($profile->current_streak * 2, 50); // Cap at 50 bonus points
        
        $totalPoints = $basePoints + $streakBonus;
        $totalXp = $baseXp + ($profile->current_streak * 5);

        DB::transaction(function () use ($user, $profile, $totalPoints, $totalXp) {
            // Update streak
            $this->updateStreak($user, 'login');
            
            // Award daily login points
            $this->awardPoints(
                $user,
                $totalPoints,
                'earned',
                'daily_login',
                null,
                'Daily Login Reward' . ($profile->current_streak > 0 ? " (Streak: {$profile->current_streak} days)" : '')
            );

            // Award XP
            $this->awardXp($user, $totalXp, 'daily_login', null);

            // Log activity
            GamificationActivity::create([
                'user_id' => $user->id,
                'action' => 'daily_login',
                'description' => 'Claimed daily login reward',
                'points_earned' => $totalPoints,
                'xp_earned' => $totalXp,
            ]);
        });

        return [
            'success' => true,
            'points' => $totalPoints,
            'xp' => $totalXp,
            'streak' => $profile->fresh()->current_streak,
            'message' => "Daily reward claimed! +{$totalPoints} points, +{$totalXp} XP",
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Leaderboard System
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Get global leaderboard.
     */
    public function getGlobalLeaderboard(int $limit = 100): \Illuminate\Support\Collection
    {
        return UserGamificationProfile::onLeaderboard()
            ->topPlayers($limit)
            ->with('user')
            ->get()
            ->map(function ($profile, $index) {
                return [
                    'rank' => $index + 1,
                    'user_id' => $profile->user_id,
                    'display_name' => $profile->display_name,
                    'level' => $profile->level,
                    'total_points' => $profile->total_points,
                    'achievements' => $profile->achievements_unlocked,
                    'badges' => $profile->badges_earned,
                ];
            });
    }

    /**
     * Get industry-specific leaderboard.
     */
    public function getIndustryLeaderboard(string $industry, int $limit = 100): \Illuminate\Support\Collection
    {
        return UserGamificationProfile::onLeaderboard()
            ->byIndustry($industry)
            ->topPlayers($limit)
            ->with('user')
            ->get()
            ->map(function ($profile, $index) {
                return [
                    'rank' => $index + 1,
                    'user_id' => $profile->user_id,
                    'display_name' => $profile->display_name,
                    'level' => $profile->level,
                    'total_points' => $profile->total_points,
                ];
            });
    }

    /**
     * Get user's global rank.
     */
    public function getUserGlobalRank(User $user): int
    {
        $profile = $this->getProfile($user);

        if (!$profile->show_on_leaderboard) {
            return 0;
        }

        return UserGamificationProfile::where('total_points', '>', $profile->total_points)
            ->onLeaderboard()
            ->count() + 1;
    }

    /**
     * Update leaderboard opt-in setting.
     */
    public function setLeaderboardVisibility(User $user, bool $visible, ?string $displayName = null): void
    {
        $profile = $this->getProfile($user);
        
        $profile->update([
            'show_on_leaderboard' => $visible,
            'leaderboard_display_name' => $displayName,
        ]);
    }

    /**
     * Refresh leaderboard rankings.
     */
    public function refreshLeaderboard(Leaderboard $leaderboard): void
    {
        $profiles = UserGamificationProfile::onLeaderboard()
            ->orderByDesc($leaderboard->metric === 'total_points' ? 'total_points' : 'total_points')
            ->get();

        $rank = 1;
        foreach ($profiles as $profile) {
            $entry = LeaderboardEntry::firstOrCreate(
                [
                    'leaderboard_id' => $leaderboard->id,
                    'user_id' => $profile->user_id,
                ],
                ['rank' => 0, 'score' => 0]
            );

            $entry->updateRank($rank, $profile->total_points);
            $rank++;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Rewards Marketplace
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Get available rewards for user.
     */
    public function getAvailableRewards(User $user): \Illuminate\Support\Collection
    {
        $profile = $this->getProfile($user);

        return Reward::available()
            ->inStock()
            ->forLevel($profile->level)
            ->orderBy('points_cost')
            ->get()
            ->map(function ($reward) use ($user) {
                $canRedeem = $reward->canBeRedeemedBy($user);
                return [
                    'reward' => $reward,
                    'can_redeem' => $canRedeem['can_redeem'],
                    'reason' => $canRedeem['reason'],
                ];
            });
    }

    /**
     * Redeem a reward.
     */
    public function redeemReward(User $user, Reward $reward): array
    {
        $canRedeem = $reward->canBeRedeemedBy($user);

        if (!$canRedeem['can_redeem']) {
            return ['success' => false, 'message' => $canRedeem['reason']];
        }

        $expiresAt = $reward->duration_days 
            ? now()->addDays($reward->duration_days) 
            : null;

        DB::transaction(function () use ($user, $reward, $expiresAt) {
            // Deduct points
            $this->spendPoints(
                $user,
                $reward->points_cost,
                'reward',
                $reward->id,
                "Redeemed: {$reward->name}"
            );

            // Create user reward
            UserReward::create([
                'user_id' => $user->id,
                'reward_id' => $reward->id,
                'points_spent' => $reward->points_cost,
                'status' => 'active',
                'redeemed_at' => now(),
                'expires_at' => $expiresAt,
            ]);

            // Increment redeem count
            $reward->incrementRedeemCount();

            // Update profile
            $profile = $this->getProfile($user);
            $profile->increment('rewards_redeemed');

            // Apply reward effect
            $this->applyRewardEffect($user, $reward);
        });

        return ['success' => true, 'message' => 'Reward redeemed successfully!'];
    }

    /**
     * Apply reward effects.
     */
    protected function applyRewardEffect(User $user, Reward $reward): void
    {
        $profile = $this->getProfile($user);

        switch ($reward->reward_type) {
            case 'xp_boost':
                $multiplier = $reward->reward_data['multiplier'] ?? 1.5;
                $duration = $reward->reward_data['duration_hours'] ?? 24;
                $profile->update([
                    'xp_multiplier' => $multiplier,
                    'multiplier_expires_at' => now()->addHours($duration),
                ]);
                break;

            case 'streak_freeze':
                $freezes = $reward->reward_data['count'] ?? 1;
                $profile->increment('streak_freeze_count', $freezes);
                break;

            case 'badge':
                $badgeSlug = $reward->reward_data['badge_slug'] ?? null;
                if ($badgeSlug) {
                    $badge = GamificationBadge::where('slug', $badgeSlug)->first();
                    if ($badge) {
                        $this->awardBadge($user, $badge);
                    }
                }
                break;

            // Add more reward types as needed
        }
    }

    /**
     * Get user's redeemed rewards.
     */
    public function getUserRewards(User $user, string $status = 'active'): \Illuminate\Support\Collection
    {
        return UserReward::forUser($user->id)
            ->where('status', $status)
            ->with('reward')
            ->orderByDesc('redeemed_at')
            ->get();
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Seasonal Events
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Get current seasonal event.
     */
    public function getCurrentEvent(): ?SeasonalEvent
    {
        return SeasonalEvent::getCurrentEvent();
    }

    /**
     * Get user's event participation.
     */
    public function getEventParticipation(User $user, SeasonalEvent $event): ?UserEventParticipation
    {
        return UserEventParticipation::where('user_id', $user->id)
            ->where('event_id', $event->id)
            ->first();
    }

    /**
     * Join a seasonal event.
     */
    public function joinEvent(User $user, SeasonalEvent $event): UserEventParticipation
    {
        return UserEventParticipation::firstOrCreate(
            [
                'user_id' => $user->id,
                'event_id' => $event->id,
            ],
            [
                'event_points' => 0,
                'event_xp' => 0,
                'tasks_completed' => 0,
            ]
        );
    }

    /**
     * Update event participation after activity.
     */
    protected function updateEventParticipation(User $user, int $points, int $xp): void
    {
        $event = $this->getCurrentEvent();
        if (!$event) {
            return;
        }

        $participation = $this->getEventParticipation($user, $event);
        if (!$participation) {
            return;
        }

        // Apply event multipliers
        $eventPoints = (int) round($points * $event->points_multiplier);
        $eventXp = (int) round($xp * $event->xp_multiplier);

        $participation->addPoints($eventPoints);
        $participation->addXp($eventXp);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Referral System
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Award referral bonus when milestone is reached.
     */
    public function awardReferralBonus(User $referrer, User $referred, string $milestone): array
    {
        $bonus = GamificationReferralBonus::createBonus(
            $referrer->id,
            $referred->id,
            $milestone
        );

        if (!$bonus) {
            return ['success' => false, 'message' => 'Bonus already awarded'];
        }

        // Award to referrer
        $this->awardPoints(
            $referrer,
            $bonus->points_awarded,
            'earned',
            'referral',
            $bonus->id,
            "Referral bonus: {$bonus->milestone_name}"
        );

        $this->awardXp($referrer, $bonus->xp_awarded, 'referral', $bonus->id);

        return [
            'success' => true,
            'points' => $bonus->points_awarded,
            'xp' => $bonus->xp_awarded,
        ];
    }

    /**
     * Get referral statistics.
     */
    public function getReferralStats(User $user): array
    {
        $bonuses = GamificationReferralBonus::forReferrer($user->id)->get();

        return [
            'total_referrals' => $bonuses->unique('referred_id')->count(),
            'total_points_earned' => $bonuses->sum('points_awarded'),
            'total_xp_earned' => $bonuses->sum('xp_awarded'),
            'milestones' => $bonuses->groupBy('milestone')
                ->map(fn ($group) => $group->count()),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Profile Completion Tracking
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Calculate profile completion percentage.
     */
    public function calculateProfileCompletion(User $user): array
    {
        $sections = [
            'basic_info' => $this->checkBasicInfo($user),
            'profile_photo' => !empty($user->avatar),
            'resume' => !empty($user->resume_path),
            'skills' => $user->skills()->count() >= 3,
            'experience' => $user->experiences()->count() > 0,
            'education' => $user->educations()->count() > 0,
            'preferences' => !empty($user->job_preferences),
        ];

        $completed = array_filter($sections);
        $percentage = (count($completed) / count($sections)) * 100;

        return [
            'percentage' => round($percentage),
            'sections' => $sections,
            'completed_count' => count($completed),
            'total_count' => count($sections),
        ];
    }

    /**
     * Check basic info completion.
     */
    protected function checkBasicInfo(User $user): bool
    {
        return !empty($user->name) && 
               !empty($user->email) && 
               !empty($user->phone);
    }

    /**
     * Award profile completion milestones.
     */
    public function checkProfileCompletionMilestones(User $user): void
    {
        $completion = $this->calculateProfileCompletion($user);

        $milestones = [
            25 => 'profile_25_complete',
            50 => 'profile_50_complete',
            75 => 'profile_75_complete',
            100 => 'profile_100_complete',
        ];

        foreach ($milestones as $threshold => $action) {
            if ($completion['percentage'] >= $threshold) {
                $alreadyTracked = GamificationActivity::forUser($user->id)
                    ->byAction($action)
                    ->exists();

                if (!$alreadyTracked) {
                    $this->trackActivity($user, $action);
                }
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Check if user can perform action (daily limits).
     */
    protected function canPerformAction(User $user, string $action): bool
    {
        // Define daily limits for certain actions
        $dailyLimits = [
            'job_viewed' => 100,
            'job_saved' => 50,
            'message_sent' => 50,
        ];

        if (!isset($dailyLimits[$action])) {
            return true;
        }

        $todayCount = GamificationActivity::getUserActionCountToday($user->id, $action);
        
        return $todayCount < $dailyLimits[$action];
    }

    /**
     * Get active points multiplier (from events, etc.).
     */
    protected function getActivePointsMultiplier(User $user): float
    {
        $event = $this->getCurrentEvent();
        
        if ($event && $event->hasUserParticipated($user->id)) {
            return (float) $event->points_multiplier;
        }

        return 1.0;
    }

    /**
     * Get activity history for user.
     */
    public function getActivityHistory(User $user, int $limit = 50): \Illuminate\Support\Collection
    {
        return GamificationActivity::forUser($user->id)
            ->recent($limit)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Get points transaction history.
     */
    public function getPointsHistory(User $user, int $days = 30): \Illuminate\Support\Collection
    {
        return PointsTransaction::forUser($user->id)
            ->recent($days)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Get XP transaction history.
     */
    public function getXpHistory(User $user, int $days = 30): \Illuminate\Support\Collection
    {
        return XpTransaction::forUser($user->id)
            ->recent($days)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Get dashboard summary data.
     */
    public function getDashboardSummary(User $user): array
    {
        return [
            'profile' => $this->getProfileStats($user),
            'today_challenges' => $this->getTodayChallenges($user),
            'recent_achievements' => UserAchievement::forUser($user->id)
                ->completed()
                ->with('achievement')
                ->orderByDesc('unlocked_at')
                ->take(5)
                ->get(),
            'featured_badges' => $this->getUserBadges($user, true)->take(6),
            'current_event' => $this->getCurrentEvent(),
            'profile_completion' => $this->calculateProfileCompletion($user),
        ];
    }
}
