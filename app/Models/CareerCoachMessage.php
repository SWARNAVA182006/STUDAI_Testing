<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareerCoachMessage extends Model
{
    use HasFactory;

    // Message Roles
    public const ROLE_USER = 'user';
    public const ROLE_ASSISTANT = 'assistant';
    public const ROLE_SYSTEM = 'system';

    protected $fillable = [
        'session_id',
        'user_id',
        'role',
        'content',
        'metadata',
        'voice_data',
        'is_voice_input',
        'is_voice_output',
        'sentiment',
        'extracted_entities',
        'tokens_used',
    ];

    protected $casts = [
        'metadata' => 'array',
        'voice_data' => 'array',
        'extracted_entities' => 'array',
        'is_voice_input' => 'boolean',
        'is_voice_output' => 'boolean',
    ];

    /**
     * Get the session that owns the message.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(CareerCoachSession::class, 'session_id');
    }

    /**
     * Get the user that owns the message.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for user messages.
     */
    public function scopeFromUser($query)
    {
        return $query->where('role', self::ROLE_USER);
    }

    /**
     * Scope for assistant messages.
     */
    public function scopeFromAssistant($query)
    {
        return $query->where('role', self::ROLE_ASSISTANT);
    }

    /**
     * Check if message is from user.
     */
    public function isFromUser(): bool
    {
        return $this->role === self::ROLE_USER;
    }

    /**
     * Check if message is from assistant.
     */
    public function isFromAssistant(): bool
    {
        return $this->role === self::ROLE_ASSISTANT;
    }

    /**
     * Get formatted content for display.
     */
    public function getFormattedContent(): string
    {
        return nl2br(e($this->content));
    }
}
