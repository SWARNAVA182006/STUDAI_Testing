<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MarketplaceMessage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'contract_id',
        'project_id',
        'sender_id',
        'recipient_id',
        'subject',
        'message',
        'attachments',
        'message_type',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    // Relationships
    public function contract(): BelongsTo
    {
        return $this->belongsTo(MarketplaceContract::class, 'contract_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(MarketplaceProject::class, 'project_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('sender_id', $userId)
              ->orWhere('recipient_id', $userId);
        });
    }

    public function scopeInquiries($query)
    {
        return $query->where('message_type', 'inquiry');
    }

    // Helpers
    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }

    public function isFromUser(int $userId): bool
    {
        return $this->sender_id === $userId;
    }
}
