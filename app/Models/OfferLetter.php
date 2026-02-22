<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class OfferLetter extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'company_id',
        'job_id',
        'candidate_id',
        'created_by',
        'template_id',
        'benefits_package_id',
        'job_title',
        'department',
        'employment_type',
        'work_location',
        'work_arrangement',
        'reporting_to',
        'base_salary',
        'salary_period',
        'currency',
        'signing_bonus',
        'annual_bonus_target',
        'bonus_structure',
        'equity_shares',
        'equity_type',
        'vesting_schedule',
        'start_date',
        'offer_expiry_date',
        'response_deadline',
        'letter_content',
        'custom_terms',
        'special_conditions',
        'status',
        'signature_provider',
        'signature_document_id',
        'signature_status',
        'sent_at',
        'viewed_at',
        'responded_at',
        'signed_at',
        'decline_reason',
        'candidate_notes',
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'signing_bonus' => 'decimal:2',
        'annual_bonus_target' => 'decimal:2',
        'equity_shares' => 'integer',
        'custom_terms' => 'array',
        'start_date' => 'date',
        'offer_expiry_date' => 'date',
        'response_deadline' => 'date',
        'sent_at' => 'datetime',
        'viewed_at' => 'datetime',
        'responded_at' => 'datetime',
        'signed_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (OfferLetter $offer) {
            if (empty($offer->uuid)) {
                $offer->uuid = (string) Str::uuid();
            }
        });
    }

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'candidate_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(OfferLetterTemplate::class, 'template_id');
    }

    public function benefitsPackage(): BelongsTo
    {
        return $this->belongsTo(BenefitsPackage::class);
    }

    public function counterOffers(): HasMany
    {
        return $this->hasMany(CounterOffer::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(OfferLetterActivity::class);
    }

    // Scopes
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForCandidate($query, int $userId)
    {
        return $query->where('candidate_id', $userId);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['sent', 'viewed', 'under_review']);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['withdrawn', 'expired', 'declined']);
    }

    public function scopeExpiring($query, int $days = 7)
    {
        return $query->where('offer_expiry_date', '<=', now()->addDays($days))
                     ->whereIn('status', ['sent', 'viewed', 'under_review']);
    }

    // Accessors
    public function getTotalCompensationAttribute(): float
    {
        $annualSalary = $this->getAnnualizedSalary();
        $signingBonus = (float) ($this->signing_bonus ?? 0);
        $bonusTarget = $annualSalary * ((float) ($this->annual_bonus_target ?? 0) / 100);
        
        return $annualSalary + $signingBonus + $bonusTarget;
    }

    public function getAnnualizedSalary(): float
    {
        $salary = (float) $this->base_salary;
        
        return match($this->salary_period) {
            'hourly' => $salary * 40 * 52,
            'weekly' => $salary * 52,
            'bi-weekly' => $salary * 26,
            'monthly' => $salary * 12,
            'annually' => $salary,
            default => $salary,
        };
    }

    public function getFormattedSalaryAttribute(): string
    {
        $symbol = match($this->currency) {
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            default => $this->currency . ' ',
        };

        return $symbol . number_format((float) $this->base_salary, 0) . '/' . $this->salary_period;
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->offer_expiry_date && $this->offer_expiry_date->isPast();
    }

    public function getCanRespondAttribute(): bool
    {
        return in_array($this->status, ['sent', 'viewed', 'under_review']) && !$this->is_expired;
    }

    public function getLatestCounterOfferAttribute(): ?CounterOffer
    {
        return $this->counterOffers()->latest()->first();
    }

    // Status helpers
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isSent(): bool
    {
        return in_array($this->status, ['sent', 'viewed', 'under_review']);
    }

    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    public function isDeclined(): bool
    {
        return $this->status === 'declined';
    }

    public function isCounterOffered(): bool
    {
        return $this->status === 'counter_offered';
    }

    public function isWithdrawn(): bool
    {
        return $this->status === 'withdrawn';
    }

    // Actions
    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $this->logActivity('sent', 'Offer letter sent to candidate');
    }

    public function markAsViewed(): void
    {
        if (!$this->viewed_at) {
            $this->update([
                'status' => 'viewed',
                'viewed_at' => now(),
            ]);

            $this->logActivity('viewed', 'Offer letter viewed by candidate');
        }
    }

    public function accept(?string $notes = null): void
    {
        $this->update([
            'status' => 'accepted',
            'responded_at' => now(),
            'candidate_notes' => $notes,
        ]);

        $this->logActivity('accepted', 'Offer accepted by candidate');
    }

    public function decline(?string $reason = null): void
    {
        $this->update([
            'status' => 'declined',
            'responded_at' => now(),
            'decline_reason' => $reason,
        ]);

        $this->logActivity('declined', 'Offer declined by candidate', ['reason' => $reason]);
    }

    public function withdraw(): void
    {
        $this->update(['status' => 'withdrawn']);
        $this->logActivity('withdrawn', 'Offer withdrawn by employer');
    }

    public function expire(): void
    {
        $this->update(['status' => 'expired']);
        $this->logActivity('expired', 'Offer expired');
    }

    public function logActivity(string $action, ?string $description = null, ?array $metadata = null): OfferLetterActivity
    {
        return $this->activities()->create([
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
