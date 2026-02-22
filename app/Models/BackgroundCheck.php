<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BackgroundCheck extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'company_id',
        'candidate_id',
        'application_id',
        'package_id',
        'requested_by',
        'provider',
        'provider_check_id',
        'provider_report_id',
        'provider_candidate_id',
        'status',
        'result',
        'adjudication',
        'consent_requested_at',
        'consent_received_at',
        'consent_expires_at',
        'consent_token',
        'consent_ip_address',
        'consent_user_agent',
        'consent_given',
        'started_at',
        'completed_at',
        'expires_at',
        'estimated_completion_days',
        'checks_requested',
        'checks_completed',
        'report_summary',
        'report_url',
        'report_pdf_path',
        'cost',
        'notes',
        'internal_notes',
        'has_flags',
        'flags',
    ];

    protected $casts = [
        'consent_token' => 'encrypted',
        'consent_ip_address' => 'encrypted',
        'consent_requested_at' => 'datetime',
        'consent_received_at' => 'datetime',
        'consent_expires_at' => 'datetime',
        'consent_given' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
        'checks_requested' => 'array',
        'checks_completed' => 'array',
        'report_summary' => 'encrypted:array',
        'cost' => 'decimal:2',
        'has_flags' => 'boolean',
        'flags' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->consent_token)) {
                $model->consent_token = Str::random(64);
            }
        });
    }

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'candidate_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(BackgroundCheckPackage::class, 'package_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BackgroundCheckItem::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(BackgroundCheckActivity::class)->orderBy('created_at', 'desc');
    }

    public function adverseAction(): HasOne
    {
        return $this->hasOne(BackgroundCheckAdverseAction::class);
    }

    // Status helpers
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isAwaitingConsent(): bool
    {
        return $this->status === 'consent_pending';
    }

    public function hasConsent(): bool
    {
        return $this->consent_given && $this->consent_received_at !== null;
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' || 
               ($this->consent_expires_at && $this->consent_expires_at->isPast() && !$this->consent_given);
    }

    public function isClear(): bool
    {
        return $this->result === 'clear';
    }

    public function requiresReview(): bool
    {
        return $this->result === 'consider' || $this->has_flags;
    }

    // Computed attributes
    public function getStatusBadgeColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'gray',
            'consent_pending' => 'warning',
            'consent_received' => 'info',
            'in_progress' => 'info',
            'completed' => $this->result === 'clear' ? 'success' : 'warning',
            'failed', 'cancelled', 'expired' => 'danger',
            default => 'gray',
        };
    }

    public function getResultBadgeColorAttribute(): string
    {
        return match($this->result) {
            'clear' => 'success',
            'consider' => 'warning',
            'adverse_action' => 'danger',
            'suspended' => 'gray',
            default => 'gray',
        };
    }

    public function getProviderNameAttribute(): string
    {
        return match($this->provider) {
            'checkr' => 'Checkr',
            'sterling' => 'Sterling',
            'goodhire' => 'GoodHire',
            default => ucfirst($this->provider),
        };
    }

    public function getProgressPercentageAttribute(): int
    {
        if ($this->isCompleted()) {
            return 100;
        }

        $total = count($this->checks_requested ?? []);
        if ($total === 0) {
            return 0;
        }

        $completed = count($this->checks_completed ?? []);
        return (int) round(($completed / $total) * 100);
    }

    // Activity logging
    public function logActivity(string $action, ?string $description = null, ?array $metadata = null, ?int $userId = null): BackgroundCheckActivity
    {
        return $this->activities()->create([
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
        ]);
    }

    // Scopes
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'consent_pending', 'consent_received', 'in_progress']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeRequiresReview($query)
    {
        return $query->where('status', 'completed')
                    ->where(function ($q) {
                        $q->where('result', 'consider')
                          ->orWhere('has_flags', true);
                    });
    }

    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }
}
