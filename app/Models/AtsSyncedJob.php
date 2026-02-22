<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ATS Synced Job - A job synced between our platform and an ATS.
 */
class AtsSyncedJob extends Model
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

    public function connection(): BelongsTo
    {
        return $this->belongsTo(AtsConnection::class, 'ats_connection_id');
    }

    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }

    public function scopeSynced($query)
    {
        return $query->where('sync_status', 'synced');
    }

    public function scopeFailed($query)
    {
        return $query->where('sync_status', 'failed');
    }
}
