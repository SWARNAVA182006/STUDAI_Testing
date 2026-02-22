<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Connection extends Model
{
    use HasFactory;

    protected $fillable = [
        'requester_id',
        'recipient_id',
        'status',
        'message',
        'connected_at',
    ];

    protected $casts = [
        'connected_at' => 'datetime',
    ];

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_BLOCKED = 'blocked';

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', self::STATUS_ACCEPTED);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    public function accept(): bool
    {
        return $this->update([
            'status' => self::STATUS_ACCEPTED,
            'connected_at' => now(),
        ]);
    }

    public function decline(): bool
    {
        return $this->update(['status' => self::STATUS_DECLINED]);
    }

    public function block(): bool
    {
        return $this->update(['status' => self::STATUS_BLOCKED]);
    }

    /**
     * Get the other user in the connection
     */
    public function getOtherUser(int $userId): ?User
    {
        if ($this->requester_id === $userId) {
            return $this->recipient;
        }
        
        if ($this->recipient_id === $userId) {
            return $this->requester;
        }
        
        return null;
    }
}
