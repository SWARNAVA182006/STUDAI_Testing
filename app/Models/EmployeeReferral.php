<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeReferral extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'referrer_id',
        'candidate_id',
        'job_id',
        'application_id',
        'status',
        'bonus_amount',
        'bonus_status',
        'bonus_paid_at',
        'referrer_notes',
        'rejection_reason',
        'resume_path',
        'reviewed_at',
        'hired_at',
    ];

    protected $casts = [
        'bonus_amount' => 'decimal:2',
        'reviewed_at' => 'datetime',
        'hired_at' => 'datetime',
        'bonus_paid_at' => 'datetime',
    ];

    /**
     * Status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_CONTACTED = 'contacted';
    const STATUS_INTERVIEWING = 'interviewing';
    const STATUS_HIRED = 'hired';
    const STATUS_REJECTED = 'rejected';

    /**
     * Bonus status constants
     */
    const BONUS_PENDING = 'pending';
    const BONUS_APPROVED = 'approved';
    const BONUS_PAID = 'paid';
    const BONUS_NOT_ELIGIBLE = 'not_eligible';

    /**
     * Get the company
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the referrer (employee who made the referral)
     */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    /**
     * Get the candidate
     */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'candidate_id');
    }

    /**
     * Get the job
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    /**
     * Get the application
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * Scope for pending referrals
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for hired referrals
     */
    public function scopeHired($query)
    {
        return $query->where('status', self::STATUS_HIRED);
    }

    /**
     * Scope for eligible bonuses
     */
    public function scopeEligibleForBonus($query)
    {
        return $query->where('status', self::STATUS_HIRED)
            ->where('bonus_status', '!=', self::BONUS_NOT_ELIGIBLE);
    }

    /**
     * Mark as hired
     */
    public function markAsHired(): void
    {
        $this->update([
            'status' => self::STATUS_HIRED,
            'hired_at' => now(),
            'bonus_status' => self::BONUS_APPROVED,
        ]);
    }

    /**
     * Mark bonus as paid
     */
    public function markBonusPaid(): void
    {
        $this->update([
            'bonus_status' => self::BONUS_PAID,
            'bonus_paid_at' => now(),
        ]);
    }

    /**
     * Check if bonus is eligible for payout (after probation period)
     */
    public function isBonusEligibleForPayout(): bool
    {
        if ($this->status !== self::STATUS_HIRED || !$this->hired_at) {
            return false;
        }

        $settings = ReferralSetting::where('company_id', $this->company_id)->first();
        $probationDays = $settings->probation_days ?? 90;

        return $this->hired_at->addDays($probationDays)->isPast();
    }
}
