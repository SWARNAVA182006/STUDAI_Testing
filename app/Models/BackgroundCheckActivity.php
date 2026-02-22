<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BackgroundCheckActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'background_check_id',
        'user_id',
        'action',
        'description',
        'metadata',
        'ip_address',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    // Relationships
    public function backgroundCheck(): BelongsTo
    {
        return $this->belongsTo(BackgroundCheck::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Helpers
    public function getActionLabelAttribute(): string
    {
        $labels = [
            'created' => 'Background check requested',
            'consent_sent' => 'Consent request sent to candidate',
            'consent_received' => 'Candidate provided consent',
            'consent_expired' => 'Consent request expired',
            'started' => 'Background check started',
            'check_completed' => 'Individual check completed',
            'completed' => 'Background check completed',
            'reviewed' => 'Results reviewed',
            'pre_adverse_sent' => 'Pre-adverse action notice sent',
            'dispute_received' => 'Candidate dispute received',
            'adverse_action_sent' => 'Final adverse action notice sent',
            'adverse_action_withdrawn' => 'Adverse action withdrawn',
            'cancelled' => 'Background check cancelled',
            'report_downloaded' => 'Report downloaded',
            'notes_updated' => 'Notes updated',
        ];

        return $labels[$this->action] ?? ucfirst(str_replace('_', ' ', $this->action));
    }

    public function getActionIconAttribute(): string
    {
        return match($this->action) {
            'created' => 'heroicon-o-plus-circle',
            'consent_sent' => 'heroicon-o-envelope',
            'consent_received' => 'heroicon-o-check-circle',
            'consent_expired' => 'heroicon-o-clock',
            'started' => 'heroicon-o-play',
            'check_completed', 'completed' => 'heroicon-o-check',
            'reviewed' => 'heroicon-o-eye',
            'pre_adverse_sent', 'adverse_action_sent' => 'heroicon-o-exclamation-triangle',
            'dispute_received' => 'heroicon-o-chat-bubble-left-right',
            'adverse_action_withdrawn' => 'heroicon-o-arrow-uturn-left',
            'cancelled' => 'heroicon-o-x-circle',
            'report_downloaded' => 'heroicon-o-document-arrow-down',
            'notes_updated' => 'heroicon-o-pencil',
            default => 'heroicon-o-information-circle',
        };
    }

    public function getActionColorAttribute(): string
    {
        return match($this->action) {
            'created' => 'info',
            'consent_received', 'completed', 'adverse_action_withdrawn' => 'success',
            'consent_expired', 'cancelled' => 'danger',
            'pre_adverse_sent', 'adverse_action_sent' => 'warning',
            default => 'gray',
        };
    }
}
