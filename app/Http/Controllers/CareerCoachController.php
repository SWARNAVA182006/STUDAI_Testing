<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CareerCoachCheckin;
use App\Models\CareerCoachPreference;
use App\Models\CareerCoachSession;
use App\Models\CareerCoachSuggestion;
use App\Models\CareerGoal;
use App\Services\AI\CareerCoachService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CareerCoachController extends Controller
{
    public function __construct(
        protected CareerCoachService $coachService
    ) {
        $this->middleware('auth');
    }

    /**
     * Display the career coach dashboard.
     */
    public function index(): View
    {
        $user = auth()->user();
        $this->coachService->forUser($user);

        // Get active sessions
        $sessions = CareerCoachSession::where('user_id', $user->id)
            ->orderBy('last_message_at', 'desc')
            ->limit(10)
            ->get();

        // Get active goals
        $goals = CareerGoal::where('user_id', $user->id)
            ->active()
            ->orderBy('priority', 'desc')
            ->get();

        // Get pending check-ins
        $pendingCheckins = $this->coachService->getPendingCheckins();

        // Get active suggestions
        $suggestions = $this->coachService->getActiveSuggestions();

        // Get preferences
        $preferences = CareerCoachPreference::getOrCreate($user);

        // Stats
        $stats = [
            'total_sessions' => CareerCoachSession::where('user_id', $user->id)->count(),
            'active_goals' => $goals->count(),
            'completed_goals' => CareerGoal::where('user_id', $user->id)->completed()->count(),
            'checkins_completed' => CareerCoachCheckin::where('user_id', $user->id)->completed()->count(),
        ];

        return view('career-coach.index', compact(
            'sessions',
            'goals',
            'pendingCheckins',
            'suggestions',
            'preferences',
            'stats'
        ));
    }

    /**
     * Start a new coaching session.
     */
    public function startSession(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|in:' . implode(',', array_keys(CareerCoachSession::getTypeLabels())),
            'title' => 'nullable|string|max:255',
        ]);

        $user = auth()->user();
        $this->coachService->forUser($user);

        $session = $this->coachService->startSession(
            $request->input('type'),
            $request->input('title')
        );

        return response()->json([
            'success' => true,
            'session' => [
                'id' => $session->id,
                'title' => $session->title,
                'type' => $session->session_type,
                'type_label' => $session->getTypeLabel(),
            ],
            'redirect' => route('career-coach.session', $session),
        ]);
    }

    /**
     * Display a specific session (chat view).
     */
    public function session(CareerCoachSession $session): View
    {
        $this->authorize('view', $session);

        $messages = $session->messages()
            ->whereIn('role', ['user', 'assistant'])
            ->orderBy('created_at')
            ->get();

        $sessionTypes = CareerCoachSession::getTypeLabels();

        return view('career-coach.session', compact('session', 'messages', 'sessionTypes'));
    }

    /**
     * Send a message in a session.
     */
    public function sendMessage(Request $request, CareerCoachSession $session): JsonResponse
    {
        $this->authorize('update', $session);

        $request->validate([
            'message' => 'required|string|max:5000',
            'is_voice' => 'boolean',
        ]);

        $user = auth()->user();
        $this->coachService->forUser($user);

        $response = $this->coachService->sendMessage(
            $session,
            $request->input('message'),
            $request->boolean('is_voice')
        );

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $response->id,
                'role' => $response->role,
                'content' => $response->content,
                'created_at' => $response->created_at->format('g:i A'),
                'metadata' => $response->metadata,
            ],
        ]);
    }

    /**
     * End a session and generate summary.
     */
    public function endSession(CareerCoachSession $session): JsonResponse
    {
        $this->authorize('update', $session);

        $user = auth()->user();
        $this->coachService->forUser($user);

        $summary = $this->coachService->generateSessionSummary($session);
        $session->markCompleted($summary);

        return response()->json([
            'success' => true,
            'summary' => $summary,
        ]);
    }

    /**
     * Get session history.
     */
    public function history(): View
    {
        $user = auth()->user();

        $sessions = CareerCoachSession::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('career-coach.history', compact('sessions'));
    }

    /**
     * Goals management page.
     */
    public function goals(): View
    {
        $user = auth()->user();

        $activeGoals = CareerGoal::where('user_id', $user->id)
            ->active()
            ->orderBy('priority', 'desc')
            ->orderBy('target_date')
            ->get();

        $completedGoals = CareerGoal::where('user_id', $user->id)
            ->completed()
            ->orderBy('completed_at', 'desc')
            ->limit(10)
            ->get();

        $categories = CareerGoal::getCategoryLabels();
        $timeframes = CareerGoal::getTimeframeLabels();
        $priorities = CareerGoal::getPriorityLabels();

        return view('career-coach.goals', compact(
            'activeGoals',
            'completedGoals',
            'categories',
            'timeframes',
            'priorities'
        ));
    }

    /**
     * Create a new goal.
     */
    public function createGoal(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'category' => 'required|string|in:' . implode(',', array_keys(CareerGoal::getCategoryLabels())),
            'timeframe' => 'required|string|in:' . implode(',', array_keys(CareerGoal::getTimeframeLabels())),
            'target_date' => 'nullable|date|after:today',
            'priority' => 'required|string|in:' . implode(',', array_keys(CareerGoal::getPriorityLabels())),
            'milestones' => 'nullable|array',
        ]);

        $user = auth()->user();

        $goal = CareerGoal::create([
            'user_id' => $user->id,
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'category' => $request->input('category'),
            'timeframe' => $request->input('timeframe'),
            'target_date' => $request->input('target_date'),
            'priority' => $request->input('priority'),
            'milestones' => $request->input('milestones'),
            'status' => CareerGoal::STATUS_NOT_STARTED,
        ]);

        return response()->json([
            'success' => true,
            'goal' => $goal,
        ]);
    }

    /**
     * Update a goal.
     */
    public function updateGoal(Request $request, CareerGoal $goal): JsonResponse
    {
        $this->authorize('update', $goal);

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:2000',
            'category' => 'sometimes|string',
            'timeframe' => 'sometimes|string',
            'target_date' => 'nullable|date',
            'priority' => 'sometimes|string',
            'status' => 'sometimes|string',
            'progress_percentage' => 'sometimes|integer|min:0|max:100',
            'notes' => 'nullable|string',
        ]);

        $goal->update($request->only([
            'title', 'description', 'category', 'timeframe',
            'target_date', 'priority', 'status', 'progress_percentage', 'notes'
        ]));

        return response()->json([
            'success' => true,
            'goal' => $goal->fresh(),
        ]);
    }

    /**
     * Update goal progress.
     */
    public function updateProgress(Request $request, CareerGoal $goal): JsonResponse
    {
        $this->authorize('update', $goal);

        $request->validate([
            'progress' => 'required|integer|min:0|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        $goal->updateProgress(
            $request->input('progress'),
            $request->input('notes')
        );

        return response()->json([
            'success' => true,
            'goal' => $goal->fresh(),
        ]);
    }

    /**
     * Delete a goal.
     */
    public function deleteGoal(CareerGoal $goal): JsonResponse
    {
        $this->authorize('delete', $goal);

        $goal->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Preferences page.
     */
    public function preferences(): View
    {
        $user = auth()->user();
        $preferences = CareerCoachPreference::getOrCreate($user);

        $styles = CareerCoachPreference::getStyleLabels();
        $frequencies = CareerCoachPreference::getFrequencyLabels();
        $days = CareerCoachPreference::getDayOptions();

        return view('career-coach.preferences', compact('preferences', 'styles', 'frequencies', 'days'));
    }

    /**
     * Update preferences.
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $request->validate([
            'weekly_checkins_enabled' => 'boolean',
            'preferred_checkin_day' => 'string|in:' . implode(',', array_keys(CareerCoachPreference::getDayOptions())),
            'preferred_checkin_time' => 'string',
            'timezone' => 'string',
            'proactive_suggestions_enabled' => 'boolean',
            'suggestion_frequency' => 'string',
            'voice_enabled' => 'boolean',
            'coaching_style' => 'string',
            'email_notifications' => 'boolean',
            'push_notifications' => 'boolean',
        ]);

        $user = auth()->user();
        $preferences = CareerCoachPreference::getOrCreate($user);

        $preferences->update($request->only([
            'weekly_checkins_enabled',
            'preferred_checkin_day',
            'preferred_checkin_time',
            'timezone',
            'proactive_suggestions_enabled',
            'suggestion_frequency',
            'voice_enabled',
            'coaching_style',
            'email_notifications',
            'push_notifications',
        ]));

        // Reschedule check-ins if settings changed
        if ($request->has('weekly_checkins_enabled') || $request->has('preferred_checkin_day')) {
            $this->coachService->forUser($user)->scheduleWeeklyCheckins();
        }

        return response()->json([
            'success' => true,
            'preferences' => $preferences->fresh(),
        ]);
    }

    /**
     * Dismiss a suggestion.
     */
    public function dismissSuggestion(CareerCoachSuggestion $suggestion): JsonResponse
    {
        $this->authorize('update', $suggestion);

        $suggestion->dismiss();

        return response()->json(['success' => true]);
    }

    /**
     * Mark suggestion as acted upon.
     */
    public function actOnSuggestion(CareerCoachSuggestion $suggestion): JsonResponse
    {
        $this->authorize('update', $suggestion);

        $suggestion->markActedUpon();

        return response()->json(['success' => true]);
    }

    /**
     * Start a weekly check-in.
     */
    public function startCheckin(CareerCoachCheckin $checkin): JsonResponse
    {
        $this->authorize('update', $checkin);

        $user = auth()->user();
        $this->coachService->forUser($user);

        $session = $this->coachService->startCheckin($checkin);

        return response()->json([
            'success' => true,
            'redirect' => route('career-coach.session', $session),
        ]);
    }

    /**
     * Skip a check-in.
     */
    public function skipCheckin(Request $request, CareerCoachCheckin $checkin): JsonResponse
    {
        $this->authorize('update', $checkin);

        $checkin->markSkipped($request->input('reason'));

        return response()->json(['success' => true]);
    }

    /**
     * Display weekly check-in page.
     */
    public function checkin(): View
    {
        $user = auth()->user();
        $this->coachService->forUser($user);

        $pendingCheckin = CareerCoachCheckin::where('user_id', $user->id)
            ->pending()
            ->first();

        $lastCheckin = CareerCoachCheckin::where('user_id', $user->id)
            ->completed()
            ->latest()
            ->first();

        $goals = CareerGoal::where('user_id', $user->id)
            ->active()
            ->get();

        return view('career-coach.checkin', [
            'pendingCheckin' => $pendingCheckin,
            'lastCheckin' => $lastCheckin,
            'goals' => $goals,
        ]);
    }

    /**
     * Process a weekly check-in.
     */
    public function processCheckin(Request $request): JsonResponse
    {
        $request->validate([
            'mood' => 'required|string|in:great,good,okay,struggling,difficult',
            'progress_summary' => 'required|string|max:2000',
            'challenges' => 'nullable|string|max:2000',
            'wins' => 'nullable|string|max:2000',
            'focus_areas' => 'nullable|array',
            'goal_updates' => 'nullable|array',
        ]);

        $user = auth()->user();
        $this->coachService->forUser($user);

        $pendingCheckin = CareerCoachCheckin::where('user_id', $user->id)
            ->pending()
            ->first();

        if (!$pendingCheckin) {
            // Create a new check-in if none pending
            $pendingCheckin = CareerCoachCheckin::create([
                'user_id' => $user->id,
                'scheduled_at' => now(),
                'status' => 'pending',
            ]);
        }

        // Process the check-in
        $result = $this->coachService->performWeeklyCheckin([
            'mood' => $request->mood,
            'progress_summary' => $request->progress_summary,
            'challenges' => $request->challenges,
            'wins' => $request->wins,
            'focus_areas' => $request->focus_areas ?? [],
        ]);

        // Update the check-in record
        $pendingCheckin->update([
            'status' => 'completed',
            'completed_at' => now(),
            'metrics' => [
                'mood' => $request->mood,
                'goals_reviewed' => count($request->goal_updates ?? []),
            ],
            'ai_summary' => $result['ai_summary'] ?? null,
        ]);

        // Update goal progress if provided
        if ($request->goal_updates) {
            foreach ($request->goal_updates as $goalId => $update) {
                $goal = CareerGoal::find($goalId);
                if ($goal && $goal->user_id === $user->id) {
                    $goal->progress = $update['progress'] ?? $goal->progress;
                    $goal->save();
                }
            }
        }

        return response()->json([
            'success' => true,
            'result' => $result,
        ]);
    }
}
