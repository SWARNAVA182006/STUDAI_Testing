<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NetworkConversation extends Model
{
    use HasFactory;

    protected $table = 'conversations';

    protected $fillable = [
        'type',
        'name',
        'created_by',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    // Conversation types
    public const TYPE_DIRECT = 'direct';
    public const TYPE_GROUP = 'group';

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class, 'conversation_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(NetworkMessage::class, 'conversation_id');
    }

    public function latestMessage()
    {
        return $this->hasOne(NetworkMessage::class, 'conversation_id')->latestOfMany();
    }

    public function isParticipant(User $user): bool
    {
        return $this->participants()->where('user_id', $user->id)->exists();
    }

    public function getParticipant(User $user): ?ConversationParticipant
    {
        return $this->participants()->where('user_id', $user->id)->first();
    }

    public function getOtherParticipant(User $user): ?User
    {
        if ($this->type !== self::TYPE_DIRECT) {
            return null;
        }

        $participant = $this->participants()
            ->where('user_id', '!=', $user->id)
            ->first();

        return $participant?->user;
    }

    public function addParticipant(User $user): ConversationParticipant
    {
        return $this->participants()->firstOrCreate(
            ['user_id' => $user->id],
            ['last_read_at' => now()]
        );
    }

    public function removeParticipant(User $user): bool
    {
        return $this->participants()->where('user_id', $user->id)->delete() > 0;
    }

    public function getUnreadCount(User $user): int
    {
        $participant = $this->getParticipant($user);
        
        if (!$participant) {
            return 0;
        }

        return $this->messages()
            ->where('sender_id', '!=', $user->id)
            ->where('created_at', '>', $participant->last_read_at ?? $participant->created_at)
            ->count();
    }

    public function markAsRead(User $user): void
    {
        $this->participants()
            ->where('user_id', $user->id)
            ->update(['last_read_at' => now()]);
    }

    public function updateLastMessageAt(): void
    {
        $this->update(['last_message_at' => now()]);
    }

    /**
     * Get or create a direct conversation between two users
     */
    public static function getOrCreateDirect(User $user1, User $user2): self
    {
        // Check if conversation already exists
        $conversation = self::where('type', self::TYPE_DIRECT)
            ->whereHas('participants', function ($q) use ($user1) {
                $q->where('user_id', $user1->id);
            })
            ->whereHas('participants', function ($q) use ($user2) {
                $q->where('user_id', $user2->id);
            })
            ->first();

        if ($conversation) {
            return $conversation;
        }

        // Create new conversation
        $conversation = self::create([
            'type' => self::TYPE_DIRECT,
            'created_by' => $user1->id,
        ]);

        $conversation->addParticipant($user1);
        $conversation->addParticipant($user2);

        return $conversation;
    }

    public function isDirect(): bool
    {
        return $this->type === self::TYPE_DIRECT;
    }

    public function isGroup(): bool
    {
        return $this->type === self::TYPE_GROUP;
    }
}
