<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mentorship extends Model
{
    use HasFactory;

    protected $table = 'mentorships';

    protected $fillable = [
        'mentor_id',
        'mentee_id',
        'status',
        'focus_areas',
        'goals',
        'sessions_completed',
        'started_at',
        'ended_at',
        'match_score',
        'metadata',
    ];

    protected $casts = [
        'focus_areas' => 'array',
        'metadata' => 'array',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'match_score' => 'decimal:2',
    ];

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    public function mentee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentee_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function accept(): void
    {
        $this->update([
            'status' => 'active',
            'started_at' => now(),
        ]);
    }

    public function cancel(): void
    {
        $this->update([
            'status' => 'cancelled',
            'ended_at' => now(),
        ]);
    }

    public function complete(): void
    {
        $this->update([
            'status' => 'completed',
            'ended_at' => now(),
        ]);
    }

    public function incrementSessions(): void
    {
        $this->increment('sessions_completed');
    }

    public function getDurationInDays(): ?int
    {
        if (!$this->started_at) {
            return null;
        }

        $endDate = $this->ended_at ?? now();
        return $this->started_at->diffInDays($endDate);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeForMentor($query, int $mentorId)
    {
        return $query->where('mentor_id', $mentorId);
    }

    public function scopeForMentee($query, int $menteeId)
    {
        return $query->where('mentee_id', $menteeId);
    }
}
