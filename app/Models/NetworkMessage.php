<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class NetworkMessage extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'messages';

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'content',
        'type',
        'attachments',
        'metadata',
        'reply_to_id',
        'is_edited',
    ];

    protected $casts = [
        'attachments' => 'array',
        'metadata' => 'array',
        'is_edited' => 'boolean',
    ];

    // Message types
    public const TYPE_TEXT = 'text';
    public const TYPE_IMAGE = 'image';
    public const TYPE_FILE = 'file';
    public const TYPE_VOICE = 'voice';
    public const TYPE_JOB_SHARE = 'job_share';
    public const TYPE_PROFILE_SHARE = 'profile_share';
    public const TYPE_SYSTEM = 'system';

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(NetworkConversation::class, 'conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(NetworkMessage::class, 'reply_to_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(NetworkMessage::class, 'reply_to_id');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(MessageRead::class, 'message_id');
    }

    public function isReadBy(User $user): bool
    {
        return $this->reads()->where('user_id', $user->id)->exists();
    }

    public function markAsRead(User $user): void
    {
        $this->reads()->firstOrCreate([
            'user_id' => $user->id,
        ], [
            'read_at' => now(),
        ]);
    }

    public function edit(string $content): bool
    {
        return $this->update([
            'content' => $content,
            'is_edited' => true,
        ]);
    }

    public function isFromUser(User $user): bool
    {
        return $this->sender_id === $user->id;
    }

    public function isReply(): bool
    {
        return $this->reply_to_id !== null;
    }

    protected static function booted(): void
    {
        static::created(function (NetworkMessage $message) {
            $message->conversation->updateLastMessageAt();
        });
    }
}
