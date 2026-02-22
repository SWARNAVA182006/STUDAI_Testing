<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'user_id',
        'role',
        'status',
        'joined_at',
        'notifications_enabled',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'notifications_enabled' => 'boolean',
    ];

    // Roles
    public const ROLE_MEMBER = 'member';
    public const ROLE_MODERATOR = 'moderator';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_OWNER = 'owner';

    // Statuses
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_BANNED = 'banned';

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approve(): bool
    {
        if ($this->status === self::STATUS_APPROVED) {
            return true;
        }

        $this->update([
            'status' => self::STATUS_APPROVED,
            'joined_at' => now(),
        ]);

        $this->group->increment('member_count');

        return true;
    }

    public function reject(): bool
    {
        return $this->update(['status' => self::STATUS_REJECTED]);
    }

    public function ban(): bool
    {
        $wasApproved = $this->status === self::STATUS_APPROVED;
        
        $this->update(['status' => self::STATUS_BANNED]);

        if ($wasApproved) {
            $this->group->decrement('member_count');
        }

        return true;
    }

    public function promote(string $role): bool
    {
        $roles = [self::ROLE_MEMBER, self::ROLE_MODERATOR, self::ROLE_ADMIN];
        
        if (!in_array($role, $roles)) {
            return false;
        }

        return $this->update(['role' => $role]);
    }

    public function isOwner(): bool
    {
        return $this->role === self::ROLE_OWNER;
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_OWNER]);
    }

    public function isModerator(): bool
    {
        return in_array($this->role, [self::ROLE_MODERATOR, self::ROLE_ADMIN, self::ROLE_OWNER]);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }
}
