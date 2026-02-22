<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'enabled',
        'auto_approve',
        'default_bonus_amount',
        'bonus_by_level',
        'probation_days',
        'max_referrals_per_employee',
        'terms_and_conditions',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'auto_approve' => 'boolean',
        'default_bonus_amount' => 'decimal:2',
        'bonus_by_level' => 'array',
        'probation_days' => 'integer',
        'max_referrals_per_employee' => 'integer',
    ];

    /**
     * Get the company
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get bonus amount for experience level
     */
    public function getBonusForLevel(string $level): float
    {
        $bonusByLevel = $this->bonus_by_level ?? [];
        return $bonusByLevel[$level] ?? $this->default_bonus_amount;
    }

    /**
     * Check if referrals are enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Check if auto-approve is enabled
     */
    public function shouldAutoApprove(): bool
    {
        return $this->auto_approve;
    }
}
