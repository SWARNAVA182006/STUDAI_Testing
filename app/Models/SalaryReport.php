<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalaryReport extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'company_id',
        'job_title',
        'department',
        'job_level',
        'employment_type',
        'location',
        'country_code',
        'is_remote',
        'currency',
        'base_salary',
        'bonus',
        'stock_value',
        'stock_options',
        'signing_bonus',
        'profit_sharing',
        'commission',
        'other_compensation',
        // 'total_compensation' is a virtual/generated column - do not include
        'pay_frequency',
        'pay_period',
        'years_experience',
        'years_of_experience',
        'years_at_company',
        'experience_level',
        'education_level',
        'benefits',
        'additional_notes',
        'satisfaction_rating',
        'is_verified',
        'verification_method',
        'verified_at',
        'status',
        'salary_date',
        'is_current_employee',
        'is_anonymous',
        'employment_start_date',
        'employment_end_date',
    ];

    protected $casts = [
        'is_remote' => 'boolean',
        'is_verified' => 'boolean',
        'benefits' => 'array',
        'base_salary' => 'integer',
        'bonus' => 'integer',
        'stock_value' => 'integer',
        'commission' => 'integer',
        'other_compensation' => 'integer',
        'total_compensation' => 'integer',
        'years_experience' => 'integer',
        'years_at_company' => 'integer',
        'satisfaction_rating' => 'integer',
        'verified_at' => 'datetime',
        'salary_date' => 'date',
    ];

    // Job level labels
    public const JOB_LEVELS = [
        'intern' => 'Intern',
        'entry' => 'Entry Level',
        'mid' => 'Mid Level',
        'senior' => 'Senior',
        'lead' => 'Lead',
        'manager' => 'Manager',
        'director' => 'Director',
        'vp' => 'VP',
        'c_level' => 'C-Level',
    ];

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForJobTitle($query, string $jobTitle)
    {
        return $query->where('job_title', 'LIKE', "%{$jobTitle}%");
    }

    public function scopeInLocation($query, string $location)
    {
        return $query->where('location', 'LIKE', "%{$location}%");
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // Accessors - Convert from cents to dollars for display
    public function getFormattedBaseSalaryAttribute(): string
    {
        return $this->formatMoney($this->base_salary);
    }

    public function getFormattedBonusAttribute(): string
    {
        return $this->formatMoney($this->bonus);
    }

    public function getFormattedTotalCompensationAttribute(): string
    {
        return $this->formatMoney($this->total_compensation);
    }

    public function getJobLevelLabelAttribute(): string
    {
        return self::JOB_LEVELS[$this->job_level] ?? $this->job_level;
    }

    // Helper to format money
    protected function formatMoney(?int $cents): string
    {
        if ($cents === null) {
            return 'N/A';
        }

        $dollars = $cents / 100;
        $symbol = $this->getCurrencySymbol();

        return $symbol . number_format($dollars, 0);
    }

    protected function getCurrencySymbol(): string
    {
        return match ($this->currency) {
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'INR' => '₹',
            'JPY' => '¥',
            'CAD' => 'C$',
            'AUD' => 'A$',
            default => $this->currency . ' ',
        };
    }

    // Calculate total compensation before saving
    public function calculateTotalCompensation(): int
    {
        return ($this->base_salary ?? 0)
            + ($this->bonus ?? 0)
            + ($this->stock_value ?? 0)
            + ($this->commission ?? 0)
            + ($this->other_compensation ?? 0);
    }

    protected static function booted(): void
    {
        // Note: total_compensation is now a virtual/generated column in the database
        // It is automatically calculated by MySQL based on base_salary + bonus + stock_options, etc.
        // No need to set it manually in the model

        static::created(function (SalaryReport $report) {
            if ($report->status === 'approved') {
                $report->company->incrementSalaryCount();
            }
        });
    }
}
