<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ATS Candidate Mapping - Maps local candidates to ATS candidate IDs.
 */
class AtsCandidateMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'ats_connection_id',
        'user_id',
        'external_candidate_id',
        'external_application_id',
        'sync_direction',
        'sync_status',
        'external_data',
        'external_profile_url',
        'last_synced_at',
        'sync_error',
    ];

    protected $casts = [
        'external_data' => 'array',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Get the ATS connection this mapping belongs to.
     */
    public function connection(): BelongsTo
    {
        return $this->belongsTo(AtsConnection::class, 'ats_connection_id');
    }

    /**
     * Get the local user (candidate) for this mapping.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get synced mappings.
     */
    public function scopeSynced($query)
    {
        return $query->where('sync_status', 'synced');
    }

    /**
     * Scope to get failed mappings.
     */
    public function scopeFailed($query)
    {
        return $query->where('sync_status', 'failed');
    }

    /**
     * Scope to get pending mappings.
     */
    public function scopePending($query)
    {
        return $query->where('sync_status', 'pending');
    }

    /**
     * Check if this mapping is synced.
     */
    public function isSynced(): bool
    {
        return $this->sync_status === 'synced';
    }

    /**
     * Mark this mapping as synced.
     */
    public function markAsSynced(array $externalData = []): void
    {
        $this->update([
            'sync_status' => 'synced',
            'last_synced_at' => now(),
            'sync_error' => null,
            'external_data' => array_merge($this->external_data ?? [], $externalData),
        ]);
    }

    /**
     * Mark this mapping as failed.
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'sync_status' => 'failed',
            'sync_error' => $error,
        ]);
    }
}
