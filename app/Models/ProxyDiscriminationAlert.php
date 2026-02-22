<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProxyDiscriminationAlert extends Model
{
    protected $table = 'scout_proxy_discrimination_alerts';

    protected $fillable = [
        'company_id',
        'job_id',
        'indicator_type',
        'discrimination_type',
        'severity',
        'correlation_strength',
        'impact_description',
        'recommendation',
        'status',
        'detected_at',
        'resolved_at',
        'resolved_by',
        'resolution_notes',
    ];

    protected $casts = [
        'correlation_strength' => 'decimal:4',
        'detected_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function mitigationActions(): HasMany
    {
        return $this->hasMany(BiasMitigationAction::class, 'alert_id');
    }

    /**
     * Scopes
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForJob($query, int $jobId)
    {
        return $query->where('job_id', $jobId);
    }

    public function scopePendingReview($query)
    {
        return $query->where('status', 'pending_review');
    }

    public function scopeInvestigating($query)
    {
        return $query->where('status', 'investigating');
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    public function scopeUnresolved($query)
    {
        return $query->whereIn('status', ['pending_review', 'investigating']);
    }

    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    public function scopeHigh($query)
    {
        return $query->whereIn('severity', ['critical', 'high']);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('discrimination_type', $type);
    }

    public function scopeByIndicator($query, string $indicator)
    {
        return $query->where('indicator_type', $indicator);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('detected_at', '>=', now()->subDays($days));
    }

    public function scopeHighCorrelation($query, float $threshold = 0.7)
    {
        return $query->where('correlation_strength', '>=', $threshold);
    }

    /**
     * Accessors
     */
    public function getIndicatorDisplayAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->indicator_type));
    }

    public function getTypeDisplayAttribute(): string
    {
        return match($this->discrimination_type) {
            'geographic' => 'Geographic Bias',
            'socioeconomic' => 'Socioeconomic Bias',
            'age_proxy' => 'Age Proxy Bias',
            'ethnic_proxy' => 'Ethnic Proxy Bias',
            'cultural_proxy' => 'Cultural Proxy Bias',
            'other' => 'Other Bias',
            default => 'Unknown'
        };
    }

    public function getSeverityDisplayAttribute(): string
    {
        return ucfirst($this->severity);
    }

    public function getSeverityColorAttribute(): string
    {
        return match($this->severity) {
            'low' => 'green',
            'medium' => 'yellow',
            'high' => 'orange',
            'critical' => 'red',
            default => 'gray'
        };
    }

    public function getStatusDisplayAttribute(): string
    {
        return match($this->status) {
            'pending_review' => 'Pending Review',
            'investigating' => 'Under Investigation',
            'resolved' => 'Resolved',
            'false_positive' => 'False Positive',
            default => 'Unknown'
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending_review' => 'yellow',
            'investigating' => 'blue',
            'resolved' => 'green',
            'false_positive' => 'gray',
            default => 'gray'
        };
    }

    public function getCorrelationPercentageAttribute(): float
    {
        return round($this->correlation_strength * 100, 2);
    }

    public function getIsResolvedAttribute(): bool
    {
        return in_array($this->status, ['resolved', 'false_positive']);
    }

    public function getIsActiveAttribute(): bool
    {
        return in_array($this->status, ['pending_review', 'investigating']);
    }

    public function getDaysOpenAttribute(): int
    {
        if ($this->is_resolved) {
            return $this->detected_at->diffInDays($this->resolved_at);
        }
        return $this->detected_at->diffInDays(now());
    }

    public function getIsCriticalAttribute(): bool
    {
        return $this->severity === 'critical';
    }

    public function getRequiresImmediateActionAttribute(): bool
    {
        return $this->is_critical && !$this->is_resolved;
    }

    /**
     * Methods
     */
    public function markAsInvestigating(): bool
    {
        $this->status = 'investigating';
        return $this->save();
    }

    public function resolve(int $userId, string $notes = null): bool
    {
        $this->status = 'resolved';
        $this->resolved_at = now();
        $this->resolved_by = $userId;
        $this->resolution_notes = $notes;
        return $this->save();
    }

    public function markAsFalsePositive(int $userId, string $notes = null): bool
    {
        $this->status = 'false_positive';
        $this->resolved_at = now();
        $this->resolved_by = $userId;
        $this->resolution_notes = $notes;
        return $this->save();
    }

    public function escalate(): bool
    {
        $this->severity = match($this->severity) {
            'low' => 'medium',
            'medium' => 'high',
            'high' => 'critical',
            default => $this->severity
        };
        return $this->save();
    }

    public function deescalate(): bool
    {
        $this->severity = match($this->severity) {
            'critical' => 'high',
            'high' => 'medium',
            'medium' => 'low',
            default => $this->severity
        };
        return $this->save();
    }

    public function getPriorityScore(): int
    {
        // Calculate priority: severity (0-4) * correlation (0-100)
        $severityScore = match($this->severity) {
            'low' => 1,
            'medium' => 2,
            'high' => 3,
            'critical' => 4,
            default => 0
        };
        
        return $severityScore * ($this->correlation_strength * 100);
    }
}
