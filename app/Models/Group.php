<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Group extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'cover_image',
        'icon',
        'industry',
        'topics',
        'created_by',
        'member_count',
        'post_count',
        'is_private',
        'requires_approval',
        'is_featured',
        'rules',
        'settings',
    ];

    protected $casts = [
        'topics' => 'array',
        'rules' => 'array',
        'settings' => 'array',
        'is_private' => 'boolean',
        'requires_approval' => 'boolean',
        'is_featured' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Group $group) {
            if (empty($group->slug)) {
                $group->slug = Str::slug($group->name);
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): HasMany
    {
        return $this->hasMany(GroupMember::class);
    }

    public function approvedMembers(): HasMany
    {
        return $this->members()->where('status', 'approved');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(GroupPost::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(NetworkEvent::class);
    }

    public function isMember(User $user): bool
    {
        return $this->members()
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->exists();
    }

    public function getMembership(User $user): ?GroupMember
    {
        return $this->members()->where('user_id', $user->id)->first();
    }

    public function isAdmin(User $user): bool
    {
        return $this->members()
            ->where('user_id', $user->id)
            ->whereIn('role', ['admin', 'owner'])
            ->exists();
    }

    public function isModerator(User $user): bool
    {
        return $this->members()
            ->where('user_id', $user->id)
            ->whereIn('role', ['moderator', 'admin', 'owner'])
            ->exists();
    }

    public function join(User $user): GroupMember
    {
        $status = $this->requires_approval ? 'pending' : 'approved';
        
        $member = $this->members()->create([
            'user_id' => $user->id,
            'role' => 'member',
            'status' => $status,
            'joined_at' => $status === 'approved' ? now() : null,
        ]);

        if ($status === 'approved') {
            $this->increment('member_count');
        }

        return $member;
    }

    public function leave(User $user): bool
    {
        $member = $this->getMembership($user);
        
        if (!$member) {
            return false;
        }

        if ($member->role === 'owner') {
            return false; // Owner cannot leave, must transfer ownership
        }

        $wasApproved = $member->status === 'approved';
        $member->delete();

        if ($wasApproved) {
            $this->decrement('member_count');
        }

        return true;
    }

    public function scopePublic($query)
    {
        return $query->where('is_private', false);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeInIndustry($query, string $industry)
    {
        return $query->where('industry', $industry);
    }
}
