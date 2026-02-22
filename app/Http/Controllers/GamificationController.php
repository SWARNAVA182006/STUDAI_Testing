<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\GamificationBadge;
use App\Models\Leaderboard;
use App\Models\Reward;
use App\Models\SeasonalEvent;
use App\Services\GamificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GamificationController extends Controller
{
    public function __construct(
        protected GamificationService $gamificationService
    ) {}

    // ─────────────────────────────────────────────────────────────────────────────
    // Dashboard & Profile
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Gamification dashboard (alias for index).
     */
    public function dashboard(): View
    {
        return $this->index();
    }

    /**
     * Gamification dashboard.
     */
    public function index(): View
    {
        $user = auth()->user();
        $summary = $this->gamificationService->getDashboardSummary($user);

        return view('gamification.dashboard', [
            'summary' => $summary,
            'profile' => $summary['profile'],
            'challenges' => $summary['today_challenges'],
            'achievements' => $summary['recent_achievements'],
            'badges' => $summary['featured_badges'],
            'event' => $summary['current_event'],
            'profileCompletion' => $summary['profile_completion'],
        ]);
    }

    /**
     * Get profile stats (API).
     */
    public function profileStats(): JsonResponse
    {
        $user = auth()->user();
        $stats = $this->gamificationService->getProfileStats($user);

        return response()->json($stats);
    }

    /**
     * Activity history.
     */
    public function activityHistory(Request $request): View
    {
        $user = auth()->user();
        $limit = $request->get('limit', 50);
        
        $activities = $this->gamificationService->getActivityHistory($user, $limit);
        $pointsHistory = $this->gamificationService->getPointsHistory($user);
        $xpHistory = $this->gamificationService->getXpHistory($user);

        return view('gamification.activity', [
            'activities' => $activities,
            'pointsHistory' => $pointsHistory,
            'xpHistory' => $xpHistory,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Achievements
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * List all achievements.
     */
    public function achievements(Request $request): View
    {
        $user = auth()->user();
        $category = $request->get('category');
        $tier = $request->get('tier');

        $query = Achievement::active()->visible();
        
        if ($category) {
            $query->byCategory($category);
        }
        
        if ($tier) {
            $query->byTier($tier);
        }

        $achievements = $query->orderBy('sort_order')->get();

        // Get user's progress for each
        $achievementsWithProgress = $achievements->map(function ($achievement) use ($user) {
            return [
                'achievement' => $achievement,
                'progress' => $achievement->getProgressFor($user),
                'is_unlocked' => $achievement->isUnlockedBy($user),
            ];
        });

        return view('gamification.achievements', [
            'achievements' => $achievementsWithProgress,
            'categories' => Achievement::CATEGORIES,
            'tiers' => Achievement::TIERS,
            'selectedCategory' => $category,
            'selectedTier' => $tier,
        ]);
    }

    /**
     * Claim achievement reward.
     */
    public function claimAchievement(int $achievementId): JsonResponse
    {
        $user = auth()->user();
        $result = $this->gamificationService->claimAchievementReward($user, $achievementId);

        return response()->json($result);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Badges
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * List all badges.
     */
    public function badges(Request $request): View
    {
        $user = auth()->user();
        $category = $request->get('category');
        $rarity = $request->get('rarity');

        $query = GamificationBadge::active()->displayable();
        
        if ($category) {
            $query->byCategory($category);
        }
        
        if ($rarity) {
            $query->byRarity($rarity);
        }

        $badges = $query->get();

        $badgesWithOwnership = $badges->map(function ($badge) use ($user) {
            return [
                'badge' => $badge,
                'is_owned' => $badge->isOwnedBy($user),
                'is_purchasable' => $badge->isPurchasable(),
            ];
        });

        $userBadges = $this->gamificationService->getUserBadges($user);

        return view('gamification.badges', [
            'badges' => $badgesWithOwnership,
            'userBadges' => $userBadges,
            'categories' => GamificationBadge::CATEGORIES,
            'rarities' => GamificationBadge::RARITIES,
            'selectedCategory' => $category,
            'selectedRarity' => $rarity,
        ]);
    }

    /**
     * Purchase a badge.
     */
    public function purchaseBadge(int $badgeId): JsonResponse
    {
        $user = auth()->user();
        $badge = GamificationBadge::findOrFail($badgeId);
        
        $result = $this->gamificationService->purchaseBadge($user, $badge);

        return response()->json($result);
    }

    /**
     * Toggle badge featured status.
     */
    public function toggleBadgeFeatured(int $badgeId): JsonResponse
    {
        $user = auth()->user();
        $success = $this->gamificationService->toggleBadgeFeatured($user, $badgeId);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Badge display updated' : 'Failed to update',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Daily Challenges
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Get today's challenges.
     */
    public function challenges(): View
    {
        $user = auth()->user();
        $challenges = $this->gamificationService->getTodayChallenges($user);
        $profile = $this->gamificationService->getProfile($user);

        return view('gamification.challenges', [
            'challenges' => $challenges,
            'streak' => $profile->current_streak,
        ]);
    }

    /**
     * Claim challenge reward.
     */
    public function claimChallenge(int $challengeId): JsonResponse
    {
        $user = auth()->user();
        $result = $this->gamificationService->claimDailyChallengeReward($user, $challengeId);

        return response()->json($result);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Leaderboards
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Display leaderboards.
     */
    public function leaderboards(Request $request): View
    {
        $user = auth()->user();
        $type = $request->get('type', 'global');
        $industry = $request->get('industry');

        $leaderboard = match ($type) {
            'industry' => $this->gamificationService->getIndustryLeaderboard($industry ?? 'Technology'),
            default => $this->gamificationService->getGlobalLeaderboard(),
        };

        $userRank = $this->gamificationService->getUserGlobalRank($user);
        $profile = $this->gamificationService->getProfile($user);

        return view('gamification.leaderboards', [
            'leaderboard' => $leaderboard,
            'userRank' => $userRank,
            'profile' => $profile,
            'type' => $type,
            'industries' => $this->getIndustries(),
            'selectedIndustry' => $industry,
        ]);
    }

    /**
     * Update leaderboard visibility settings.
     */
    public function updateLeaderboardSettings(Request $request): RedirectResponse
    {
        $request->validate([
            'show_on_leaderboard' => 'boolean',
            'display_name' => 'nullable|string|max:50',
            'primary_industry' => 'nullable|string|max:100',
        ]);

        $user = auth()->user();
        
        $this->gamificationService->setLeaderboardVisibility(
            $user,
            $request->boolean('show_on_leaderboard'),
            $request->get('display_name')
        );

        $profile = $this->gamificationService->getProfile($user);
        $profile->update(['primary_industry' => $request->get('primary_industry')]);

        return back()->with('success', 'Leaderboard settings updated');
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Rewards Marketplace
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Display rewards marketplace.
     */
    public function rewards(Request $request): View
    {
        $user = auth()->user();
        $category = $request->get('category');

        $query = Reward::available()->inStock();
        
        if ($category) {
            $query->byCategory($category);
        }

        $rewards = $query->orderBy('points_cost')->get();
        
        $rewardsWithStatus = $rewards->map(function ($reward) use ($user) {
            $canRedeem = $reward->canBeRedeemedBy($user);
            return [
                'reward' => $reward,
                'can_redeem' => $canRedeem['can_redeem'],
                'reason' => $canRedeem['reason'],
            ];
        });

        $profile = $this->gamificationService->getProfile($user);
        $userRewards = $this->gamificationService->getUserRewards($user);

        return view('gamification.rewards', [
            'rewards' => $rewardsWithStatus,
            'userRewards' => $userRewards,
            'profile' => $profile,
            'categories' => Reward::CATEGORIES,
            'selectedCategory' => $category,
        ]);
    }

    /**
     * Redeem a reward.
     */
    public function redeemReward(int $rewardId): JsonResponse
    {
        $user = auth()->user();
        $reward = Reward::findOrFail($rewardId);
        
        $result = $this->gamificationService->redeemReward($user, $reward);

        return response()->json($result);
    }

    /**
     * View user's redeemed rewards.
     */
    public function myRewards(): View
    {
        $user = auth()->user();
        
        $activeRewards = $this->gamificationService->getUserRewards($user, 'active');
        $usedRewards = $this->gamificationService->getUserRewards($user, 'used');
        $expiredRewards = $this->gamificationService->getUserRewards($user, 'expired');

        return view('gamification.my-rewards', [
            'activeRewards' => $activeRewards,
            'usedRewards' => $usedRewards,
            'expiredRewards' => $expiredRewards,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Seasonal Events
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Display current event.
     */
    public function events(): View
    {
        $user = auth()->user();
        $currentEvent = $this->gamificationService->getCurrentEvent();
        
        $participation = null;
        if ($currentEvent) {
            $participation = $this->gamificationService->getEventParticipation($user, $currentEvent);
        }

        $upcomingEvents = SeasonalEvent::upcoming()->get();
        $pastEvents = SeasonalEvent::past()->take(5)->get();

        return view('gamification.events', [
            'currentEvent' => $currentEvent,
            'participation' => $participation,
            'upcomingEvents' => $upcomingEvents,
            'pastEvents' => $pastEvents,
        ]);
    }

    /**
     * Join current event.
     */
    public function joinEvent(int $eventId): JsonResponse
    {
        $user = auth()->user();
        $event = SeasonalEvent::findOrFail($eventId);

        if (!$event->isRunning()) {
            return response()->json([
                'success' => false,
                'message' => 'This event is not currently running',
            ]);
        }

        $participation = $this->gamificationService->joinEvent($user, $event);

        return response()->json([
            'success' => true,
            'message' => 'Successfully joined the event!',
            'participation' => $participation,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Referrals
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Referral dashboard.
     */
    public function referrals(): View
    {
        $user = auth()->user();
        $stats = $this->gamificationService->getReferralStats($user);

        return view('gamification.referrals', [
            'stats' => $stats,
            'referralCode' => $user->referral_code ?? substr(md5($user->email), 0, 8),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Profile Completion
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Get profile completion status.
     */
    public function profileCompletion(): JsonResponse
    {
        $user = auth()->user();
        $completion = $this->gamificationService->calculateProfileCompletion($user);

        return response()->json($completion);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // API Endpoints
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Get user XP and level info (for navbar/header display).
     */
    public function quickStats(): JsonResponse
    {
        $user = auth()->user();
        $profile = $this->gamificationService->getProfile($user);

        return response()->json([
            'level' => $profile->level,
            'xp_current' => $profile->xp_current,
            'xp_required' => $profile->xp_required,
            'xp_progress' => $profile->level_progress,
            'points' => $profile->available_points,
            'streak' => $profile->current_streak,
        ]);
    }

    /**
     * Use streak freeze.
     */
    public function useStreakFreeze(): JsonResponse
    {
        $user = auth()->user();
        $success = $this->gamificationService->useStreakFreeze($user);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Streak freeze used!' : 'No streak freezes available',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Get list of industries for leaderboards.
     */
    protected function getIndustries(): array
    {
        return [
            'Technology',
            'Healthcare',
            'Finance',
            'Education',
            'Marketing',
            'Design',
            'Engineering',
            'Sales',
            'Human Resources',
            'Operations',
        ];
    }

    /**
     * Claim daily login reward.
     */
    public function claimDailyReward(): JsonResponse
    {
        $user = auth()->user();
        $result = $this->gamificationService->claimDailyLoginReward($user);

        return response()->json($result);
    }

    /**
     * Toggle leaderboard opt-in setting.
     */
    public function toggleLeaderboardOptIn(Request $request): JsonResponse
    {
        $user = auth()->user();
        $profile = $this->gamificationService->getProfile($user);
        
        $newValue = !$profile->show_on_leaderboard;
        $profile->update(['show_on_leaderboard' => $newValue]);

        return response()->json([
            'success' => true,
            'show_on_leaderboard' => $newValue,
            'message' => $newValue ? 'You are now visible on leaderboards!' : 'You are now hidden from leaderboards.',
        ]);
    }

    /**
     * Points and XP history.
     */
    public function history(Request $request): View
    {
        $user = auth()->user();
        $type = $request->get('type', 'all');
        $limit = $request->get('limit', 50);
        
        $activities = $this->gamificationService->getActivityHistory($user, $limit);
        $pointsHistory = $this->gamificationService->getPointsHistory($user);
        $xpHistory = $this->gamificationService->getXpHistory($user);

        return view('gamification.history', [
            'activities' => $activities,
            'pointsHistory' => $pointsHistory,
            'xpHistory' => $xpHistory,
            'type' => $type,
        ]);
    }

    /**
     * Detailed stats view.
     */
    public function stats(): View
    {
        $user = auth()->user();
        $profile = $this->gamificationService->getProfile($user);
        $stats = $this->gamificationService->getProfileStats($user);

        return view('gamification.stats', [
            'profile' => $profile,
            'stats' => $stats,
        ]);
    }

    /**
     * Get user profile via API.
     */
    public function getProfile(): JsonResponse
    {
        $user = auth()->user();
        $profile = $this->gamificationService->getProfile($user);
        $stats = $this->gamificationService->getProfileStats($user);

        return response()->json([
            'profile' => $profile,
            'stats' => $stats,
        ]);
    }

    /**
     * Get recent gamification notifications.
     */
    public function getNotifications(): JsonResponse
    {
        $user = auth()->user();
        
        // Get recent achievements, badges, level-ups
        $activities = $this->gamificationService->getActivityHistory($user, 10);
        
        $notifications = $activities->map(function ($activity) {
            return [
                'id' => $activity->id,
                'type' => $activity->activity_type,
                'title' => $this->getNotificationTitle($activity),
                'message' => $activity->description ?? '',
                'points_earned' => $activity->points_earned ?? 0,
                'xp_earned' => $activity->xp_earned ?? 0,
                'created_at' => $activity->created_at->diffForHumans(),
                'icon' => $this->getNotificationIcon($activity->activity_type),
            ];
        });

        return response()->json([
            'notifications' => $notifications,
            'count' => $notifications->count(),
        ]);
    }

    /**
     * Get notification title based on activity.
     */
    protected function getNotificationTitle($activity): string
    {
        return match ($activity->activity_type) {
            'achievement_unlocked' => '🏆 Achievement Unlocked!',
            'badge_earned' => '🎖️ New Badge Earned!',
            'level_up' => '⬆️ Level Up!',
            'points_earned' => '💰 Points Earned',
            'xp_earned' => '✨ XP Gained',
            'streak_bonus' => '🔥 Streak Bonus!',
            'challenge_completed' => '🎯 Challenge Complete!',
            'reward_redeemed' => '🎁 Reward Claimed!',
            default => '📣 Activity',
        };
    }

    /**
     * Get notification icon based on type.
     */
    protected function getNotificationIcon(string $type): string
    {
        return match ($type) {
            'achievement_unlocked' => '🏆',
            'badge_earned' => '🎖️',
            'level_up' => '⬆️',
            'points_earned' => '💰',
            'xp_earned' => '✨',
            'streak_bonus' => '🔥',
            'challenge_completed' => '🎯',
            'reward_redeemed' => '🎁',
            default => '📣',
        };
    }
}
