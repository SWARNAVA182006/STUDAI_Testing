<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ATS Sync Log - Logs sync operations between our platform and ATS.
 */
class AtsSyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'ats_connection_id',
        'ats_provider_id',
        'sync_type',
        'direction',
        'status',
        'records_processed',
        'records_created',
        'records_updated',
        'records_failed',
        'error_details',
        'metadata',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'records_processed' => 'integer',
        'records_created' => 'integer',
        'records_updated' => 'integer',
        'records_failed' => 'integer',
        'error_details' => 'array',
        'metadata' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the ATS connection for this log.
     */
    public function connection(): BelongsTo
    {
        return $this->belongsTo(AtsConnection::class, 'ats_connection_id');
    }

    /**
     * Get the ATS provider for this log.
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(AtsProvider::class, 'ats_provider_id');
    }

    /**
     * Scope to get completed syncs.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get failed syncs.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope to get running syncs.
     */
    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    /**
     * Scope to get candidate syncs.
     */
    public function scopeCandidates($query)
    {
        return $query->where('sync_type', 'candidates');
    }

    /**
     * Scope to get job syncs.
     */
    public function scopeJobs($query)
    {
        return $query->where('sync_type', 'jobs');
    }

    /**
     * Get the duration of the sync in seconds.
     */
    public function getDurationAttribute(): ?int
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }

        return $this->completed_at->diffInSeconds($this->started_at);
    }

    /**
     * Check if the sync was successful.
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'completed' && $this->records_failed === 0;
    }

    /**
     * Check if the sync had errors.
     */
    public function hasErrors(): bool
    {
        return $this->records_failed > 0 || !empty($this->error_details);
    }

    /**
     * Start a new sync log.
     */
    public static function startSync(int $connectionId, string $syncType, string $direction, array $metadata = []): self
    {
        return self::create([
            'ats_connection_id' => $connectionId,
            'sync_type' => $syncType,
            'direction' => $direction,
            'status' => 'running',
            'records_processed' => 0,
            'records_created' => 0,
            'records_updated' => 0,
            'records_failed' => 0,
            'metadata' => $metadata,
            'started_at' => now(),
        ]);
    }

    /**
     * Mark sync as completed.
     */
    public function complete(int $processed = 0, int $created = 0, int $updated = 0, int $failed = 0): void
    {
        $this->update([
            'status' => 'completed',
            'records_processed' => $processed,
            'records_created' => $created,
            'records_updated' => $updated,
            'records_failed' => $failed,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark sync as failed.
     */
    public function fail(string $error, array $details = []): void
    {
        $this->update([
            'status' => 'failed',
            'error_details' => array_merge(['message' => $error], $details),
            'completed_at' => now(),
        ]);
    }
}
