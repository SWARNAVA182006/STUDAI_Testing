<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BackgroundCheckWebhook extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'event_type',
        'provider_check_id',
        'payload',
        'processed',
        'processed_at',
        'processing_notes',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed' => 'boolean',
        'processed_at' => 'datetime',
    ];

    // Scopes
    public function scopeUnprocessed($query)
    {
        return $query->where('processed', false);
    }

    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    // Methods
    public function markAsProcessed(?string $notes = null): void
    {
        $this->update([
            'processed' => true,
            'processed_at' => now(),
            'processing_notes' => $notes,
        ]);
    }
}
