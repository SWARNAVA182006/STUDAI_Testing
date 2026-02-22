<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyTeamMember extends Model
{
    protected $fillable = [
        'company_id',
        'user_id',
        'role',
        'permissions',
        'is_active',
        'invited_at',
        'joined_at',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_active' => 'boolean',
        'invited_at' => 'datetime',
        'joined_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if member has specific permission
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->role === 'owner' || $this->role === 'admin') {
            return true;
        }

        return in_array($permission, $this->permissions ?? []);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }
}
