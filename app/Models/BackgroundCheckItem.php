<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BackgroundCheckItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'background_check_id',
        'check_type',
        'status',
        'result',
        'result_data',
        'notes',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'result_data' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Relationships
    public function backgroundCheck(): BelongsTo
    {
        return $this->belongsTo(BackgroundCheck::class);
    }

    // Helpers
    public function getCheckTypeLabelAttribute(): string
    {
        $labels = [
            'criminal' => 'Criminal Records',
            'employment_verification' => 'Employment Verification',
            'education_verification' => 'Education Verification',
            'credit' => 'Credit Check',
            'drug_screening' => 'Drug Screening',
            'mvr' => 'Motor Vehicle Records',
            'identity' => 'Identity Verification',
            'ssn_trace' => 'SSN Trace',
            'sex_offender' => 'Sex Offender Registry',
            'global_watchlist' => 'Global Watchlist',
            'professional_license' => 'Professional License Verification',
            'reference_check' => 'Reference Check',
        ];

        return $labels[$this->check_type] ?? ucfirst(str_replace('_', ' ', $this->check_type));
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'gray',
            'in_progress' => 'info',
            'completed' => $this->result === 'clear' ? 'success' : 'warning',
            'failed', 'cancelled' => 'danger',
            default => 'gray',
        };
    }

    public function getResultBadgeColorAttribute(): string
    {
        return match($this->result) {
            'clear' => 'success',
            'consider' => 'warning',
            'adverse' => 'danger',
            default => 'gray',
        };
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isClear(): bool
    {
        return $this->result === 'clear';
    }
}
