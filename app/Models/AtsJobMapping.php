<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ATS Job Mapping - Maps local job postings to ATS job/requisition IDs.
 */
class AtsJobMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'ats_connection_id',
        'job_posting_id',
        'external_job_id',
        'external_requisition_id',
        'sync_direction',
        'sync_status',
        'external_data',
        'field_mappings',
        'external_url',
        'external_created_at',
        'external_updated_at',
        'last_synced_at',
        'sync_error',
    ];

    protected $casts = [
        'external_data' => 'array',
        'field_mappings' => 'array',
        'external_created_at' => 'datetime',
        'external_updated_at' => 'datetime',
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
     * Get the local job posting for this mapping.
     */
    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
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
     * Check if this is an inbound sync (ATS -> Local).
     */
    public function isInbound(): bool
    {
        return $this->sync_direction === 'inbound';
    }

    /**
     * Check if this is an outbound sync (Local -> ATS).
     */
    public function isOutbound(): bool
    {
        return $this->sync_direction === 'outbound';
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
