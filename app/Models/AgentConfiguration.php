<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AgentConfiguration extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Cache key for global kill switch.
     */
    public const GLOBAL_KILL_SWITCH_KEY = 'agent:global_kill_switch';

    /**
     * Cache TTL for kill switch (1 hour).
     */
    protected const KILL_SWITCH_TTL = 3600;

    protected $fillable = [
        'user_id',
        'is_active',
        'daily_application_limit',
        'applications_this_month',
        'applications_today',
        'applications_today_date',
        'target_roles',
        'preferred_locations',
        'required_skills',
        'nice_to_have_skills',
        'min_salary',
        'max_salary',
        'salary_period',
        'company_sizes',
        'work_arrangements',
        'employment_types',
        'min_experience_years',
        'max_experience_years',
        'industries',
        'excluded_keywords',
        'only_verified_companies',
        'require_visa_sponsorship',
        'application_aggressiveness',
        'match_threshold_percentage',
        'auto_follow_up',
        'require_approval',
        'approval_threshold',
        'follow_up_days',
        'enable_learning',
        'learning_metrics',
        'last_optimization_at',
        'active_hours',
        'active_days',
        'next_run_at',
        'last_run_at',
        'emergency_stopped_at',
        'emergency_stopped_by',
        'emergency_stop_reason',
        'is_globally_stopped',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'daily_application_limit' => 'integer',
        'applications_this_month' => 'integer',
        'applications_today' => 'integer',
        'applications_today_date' => 'date',
        'target_roles' => 'array',
        'preferred_locations' => 'array',
        'required_skills' => 'array',
        'nice_to_have_skills' => 'array',
        'min_salary' => 'integer',
        'max_salary' => 'integer',
        'company_sizes' => 'array',
        'work_arrangements' => 'array',
        'employment_types' => 'array',
        'min_experience_years' => 'integer',
        'max_experience_years' => 'integer',
        'industries' => 'array',
        'excluded_keywords' => 'array',
        'only_verified_companies' => 'boolean',
        'require_visa_sponsorship' => 'boolean',
        'match_threshold_percentage' => 'integer',
        'auto_follow_up' => 'boolean',
        'require_approval' => 'boolean',
        'approval_threshold' => 'integer',
        'follow_up_days' => 'integer',
        'enable_learning' => 'boolean',
        'learning_metrics' => 'array',
        'last_optimization_at' => 'datetime',
        'active_hours' => 'array',
        'active_days' => 'array',
        'next_run_at' => 'datetime',
        'last_run_at' => 'datetime',
        'emergency_stopped_at' => 'datetime',
        'is_globally_stopped' => 'boolean',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function jobMatches()
    {
        return $this->hasMany(JobMatch::class, 'user_id', 'user_id');
    }

    public function autoApplications()
    {
        return $this->hasMany(AutoApplication::class, 'user_id', 'user_id');
    }

    public function learningMetrics()
    {
        return $this->hasOne(AgentLearningMetric::class, 'user_id', 'user_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeReadyToRun($query)
    {
        return $query->active()
            ->where('next_run_at', '<=', now())
            ->orWhereNull('next_run_at');
    }

    // Business Logic
    public function activate(): void
    {
        $this->update([
            'is_active' => true,
            'next_run_at' => now(),
        ]);
    }

    public function deactivate(): void
    {
        $this->update([
            'is_active' => false,
            'next_run_at' => null,
        ]);
    }

    public function canApplyToday(): bool
    {
        $appliedToday = AutoApplication::where('user_id', $this->user_id)
            ->whereDate('created_at', today())
            ->count();

        return $appliedToday < $this->daily_application_limit;
    }

    public function getRemainingApplicationsToday(): int
    {
        $appliedToday = AutoApplication::where('user_id', $this->user_id)
            ->whereDate('created_at', today())
            ->count();

        return max(0, $this->daily_application_limit - $appliedToday);
    }

    public function updateRunSchedule(): void
    {
        // Schedule next run based on active hours and days
        $this->update([
            'last_run_at' => now(),
            'next_run_at' => $this->calculateNextRunTime(),
        ]);
    }

    public function calculateNextRunTime(): \DateTime
    {
        // Run every 4 hours during active hours
        $nextRun = now()->addHours(4);

        // If active_days specified, ensure it's a valid day
        if (!empty($this->active_days)) {
            while (!in_array($nextRun->dayOfWeek, $this->active_days)) {
                $nextRun->addDay();
            }
        }

        return $nextRun;
    }

    public function isInActiveHours(): bool
    {
        if (empty($this->active_hours)) {
            return true; // No restrictions
        }

        // Ensure active_hours is an array
        $activeHours = $this->active_hours;
        if (is_string($activeHours)) {
            $activeHours = json_decode($activeHours, true);
        }
        
        if (!is_array($activeHours)) {
            return true; // Invalid format, allow by default
        }

        $currentTime = now()->format('H:i');
        
        // Handle both formats: array of ranges or object with start/end
        if (isset($activeHours['start']) && isset($activeHours['end'])) {
            return $currentTime >= $activeHours['start'] && $currentTime <= $activeHours['end'];
        }

        // Handle array of time ranges
        foreach ($activeHours as $range) {
            if (is_string($range) && str_contains($range, '-')) {
                [$start, $end] = explode('-', $range);
                if ($currentTime >= $start && $currentTime <= $end) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getAggressivenessMultiplier(): float
    {
        return match ($this->application_aggressiveness) {
            'conservative' => 0.7,
            'aggressive' => 1.3,
            default => 1.0, // moderate
        };
    }

    public function incrementApplicationsThisMonth(): void
    {
        // Reset counter if new month
        if ($this->updated_at->month !== now()->month) {
            $this->applications_this_month = 0;
        }

        $this->increment('applications_this_month');
    }

    public function hasReachedMonthlyLimit(): bool
    {
        $monthlyLimit = $this->user->subscription?->getMonthlyAutoApplicationLimit() ?? 0;
        return $this->applications_this_month >= $monthlyLimit;
    }

    // Human-in-the-Loop Approval Methods

    /**
     * Check if approval is required for a specific match.
     *
     * Returns true if:
     * - require_approval is enabled globally for this agent, OR
     * - the match score is below the approval_threshold
     */
    public function requiresApprovalForMatch(float $matchScore): bool
    {
        // If global approval is required, always need approval
        if ($this->require_approval) {
            return true;
        }

        // If match score is below approval threshold, need approval
        $threshold = $this->approval_threshold ?? 80;
        return $matchScore < $threshold;
    }

    /**
     * Get the reason why approval is required.
     */
    public function getApprovalReason(float $matchScore): ?string
    {
        if ($this->require_approval) {
            return 'Your agent is configured to require manual approval for all applications';
        }

        $threshold = $this->approval_threshold ?? 80;
        if ($matchScore < $threshold) {
            return "Match score ({$matchScore}%) is below your configured threshold ({$threshold}%)";
        }

        return null;
    }

    /**
     * Get today's application count with date reset.
     */
    public function getApplicationsToday(): int
    {
        // Reset if it's a new day
        if ($this->applications_today_date?->isToday() !== true) {
            $this->update([
                'applications_today' => 0,
                'applications_today_date' => today(),
            ]);
            return 0;
        }

        return $this->applications_today ?? 0;
    }

    /**
     * Increment today's application counter.
     */
    public function incrementApplicationsToday(): void
    {
        // Reset if it's a new day
        if ($this->applications_today_date?->isToday() !== true) {
            $this->update([
                'applications_today' => 1,
                'applications_today_date' => today(),
            ]);
            return;
        }

        $this->increment('applications_today');
    }

    /**
     * Check if daily hard cap has been reached.
     */
    public function hasReachedDailyHardCap(): bool
    {
        $dailyCap = $this->daily_application_limit ?? 10;
        return $this->getApplicationsToday() >= $dailyCap;
    }

    // Emergency Stop Methods

    /**
     * Emergency stop this agent.
     */
    public function emergencyStop(int $stoppedBy, string $reason): void
    {
        $this->update([
            'is_active' => false,
            'emergency_stopped_at' => now(),
            'emergency_stopped_by' => $stoppedBy,
            'emergency_stop_reason' => $reason,
            'next_run_at' => null,
        ]);

        Log::warning('Agent emergency stopped', [
            'user_id' => $this->user_id,
            'stopped_by' => $stoppedBy,
            'reason' => $reason,
        ]);
    }

    /**
     * Check if this agent is emergency stopped.
     */
    public function isEmergencyStopped(): bool
    {
        return $this->emergency_stopped_at !== null;
    }

    /**
     * Clear emergency stop state.
     */
    public function clearEmergencyStop(): void
    {
        $this->update([
            'emergency_stopped_at' => null,
            'emergency_stopped_by' => null,
            'emergency_stop_reason' => null,
        ]);

        Log::info('Agent emergency stop cleared', [
            'user_id' => $this->user_id,
        ]);
    }

    /**
     * Get the user who triggered the emergency stop.
     */
    public function stoppedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'emergency_stopped_by');
    }

    // Global Kill Switch Methods

    /**
     * Activate global kill switch - stops ALL agents.
     */
    public static function activateGlobalKillSwitch(int $adminId, string $reason): int
    {
        Cache::put(self::GLOBAL_KILL_SWITCH_KEY, [
            'activated_at' => now()->toIso8601String(),
            'activated_by' => $adminId,
            'reason' => $reason,
        ], self::KILL_SWITCH_TTL);

        // Update all active agents
        $count = static::where('is_active', true)->update([
            'is_active' => false,
            'is_globally_stopped' => true,
            'emergency_stopped_at' => now(),
            'emergency_stopped_by' => $adminId,
            'emergency_stop_reason' => "Global kill switch: {$reason}",
        ]);

        Log::critical('Global agent kill switch activated', [
            'admin_id' => $adminId,
            'reason' => $reason,
            'agents_stopped' => $count,
        ]);

        return $count;
    }

    /**
     * Deactivate global kill switch.
     */
    public static function deactivateGlobalKillSwitch(): void
    {
        Cache::forget(self::GLOBAL_KILL_SWITCH_KEY);

        // Clear globally stopped flag but don't auto-reactivate
        static::where('is_globally_stopped', true)->update([
            'is_globally_stopped' => false,
        ]);

        Log::info('Global agent kill switch deactivated');
    }

    /**
     * Check if global kill switch is active.
     */
    public static function isGlobalKillSwitchActive(): bool
    {
        return Cache::has(self::GLOBAL_KILL_SWITCH_KEY);
    }

    /**
     * Get global kill switch info.
     */
    public static function getGlobalKillSwitchInfo(): ?array
    {
        return Cache::get(self::GLOBAL_KILL_SWITCH_KEY);
    }

    /**
     * Check if agent can run (not stopped and no global kill switch).
     */
    public function canRun(): bool
    {
        if (static::isGlobalKillSwitchActive()) {
            return false;
        }

        if ($this->isEmergencyStopped()) {
            return false;
        }

        if (!$this->is_active) {
            return false;
        }

        return true;
    }

    /**
     * Scope to get agents that are not emergency stopped.
     */
    public function scopeNotEmergencyStopped($query)
    {
        return $query->whereNull('emergency_stopped_at');
    }

    /**
     * Scope to get agents that are emergency stopped.
     */
    public function scopeEmergencyStopped($query)
    {
        return $query->whereNotNull('emergency_stopped_at');
    }

    /**
     * Scope to get agents that are globally stopped.
     */
    public function scopeGloballyStopped($query)
    {
        return $query->where('is_globally_stopped', true);
    }
}
