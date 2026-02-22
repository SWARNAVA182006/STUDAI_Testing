<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateInteraction extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'application_id',
        'job_id',
        'interaction_type',
        'interaction_channel',
        'interaction_summary',
        'interaction_metadata',
        'automated',
        'initiated_by_user_id',
        'candidate_sentiment',
        'candidate_feedback',
        'response_time_hours',
        'interacted_at',
    ];

    protected $casts = [
        'interaction_metadata' => 'array',
        'automated' => 'boolean',
        'interacted_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by_user_id');
    }

    /**
     * Scopes
     */
    public function scopeForCandidate($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('interacted_at', '>=', now()->subDays($days));
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('interaction_type', $type);
    }

    public function scopePositiveSentiment($query)
    {
        return $query->where('candidate_sentiment', 'positive');
    }

    public function scopeNegativeSentiment($query)
    {
        return $query->where('candidate_sentiment', 'negative');
    }

    public function scopeAutomated($query)
    {
        return $query->where('automated', true);
    }

    public function scopeManual($query)
    {
        return $query->where('automated', false);
    }

    /**
     * Helper methods
     */
    public static function recordInteraction(array $data): self
    {
        return self::create([
            'company_id' => $data['company_id'],
            'user_id' => $data['user_id'],
            'application_id' => $data['application_id'] ?? null,
            'job_id' => $data['job_id'] ?? null,
            'interaction_type' => $data['interaction_type'],
            'interaction_channel' => $data['interaction_channel'] ?? 'email',
            'interaction_summary' => $data['interaction_summary'] ?? null,
            'interaction_metadata' => $data['interaction_metadata'] ?? [],
            'automated' => $data['automated'] ?? false,
            'initiated_by_user_id' => $data['initiated_by_user_id'] ?? null,
            'candidate_sentiment' => $data['candidate_sentiment'] ?? 'unknown',
            'candidate_feedback' => $data['candidate_feedback'] ?? null,
            'response_time_hours' => $data['response_time_hours'] ?? null,
            'interacted_at' => $data['interacted_at'] ?? now(),
        ]);
    }
}
