<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResumeAISuggestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'resume_id',
        'section',
        'suggestion_type',
        'original_content',
        'suggested_content',
        'reasoning',
        'confidence_score',
        'status',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'confidence_score' => 'integer',
    ];

    /**
     * Relationships
     */
    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class);
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeSection($query, string $section)
    {
        return $query->where('section', $section);
    }

    public function scopeType($query, string $type)
    {
        return $query->where('suggestion_type', $type);
    }

    public function scopeHighConfidence($query, int $threshold = 70)
    {
        return $query->where('confidence_score', '>=', $threshold);
    }

    /**
     * Accept the suggestion
     */
    public function accept(): void
    {
        $this->update(['status' => 'accepted']);
    }

    /**
     * Reject the suggestion
     */
    public function reject(): void
    {
        $this->update(['status' => 'rejected']);
    }

    /**
     * Mark as modified
     */
    public function markAsModified(): void
    {
        $this->update(['status' => 'modified']);
    }

    /**
     * Get confidence level
     */
    public function getConfidenceLevel(): string
    {
        if ($this->confidence_score >= 80) return 'high';
        if ($this->confidence_score >= 60) return 'medium';
        return 'low';
    }
}
