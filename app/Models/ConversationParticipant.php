<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'user_id',
        'last_read_at',
        'is_muted',
        'is_archived',
    ];

    protected $casts = [
        'last_read_at' => 'datetime',
        'is_muted' => 'boolean',
        'is_archived' => 'boolean',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(NetworkConversation::class, 'conversation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mute(): bool
    {
        return $this->update(['is_muted' => true]);
    }

    public function unmute(): bool
    {
        return $this->update(['is_muted' => false]);
    }

    public function archive(): bool
    {
        return $this->update(['is_archived' => true]);
    }

    public function unarchive(): bool
    {
        return $this->update(['is_archived' => false]);
    }

    public function markAsRead(): void
    {
        $this->update(['last_read_at' => now()]);
    }
}
