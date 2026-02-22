<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeatureFlag extends Model
{
    protected $fillable = [
        'name',
        'key',
        'description',
        'enabled',
        'rollout_percentage',
        'user_ids',
        'metadata',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'rollout_percentage' => 'integer',
        'user_ids' => 'array',
        'metadata' => 'array',
    ];

    public function isEnabledFor($userId = null): bool
    {
        if (!$this->enabled) {
            return false;
        }

        // Check if specific user IDs are set
        if ($this->user_ids && is_array($this->user_ids)) {
            return in_array($userId, $this->user_ids);
        }

        // Check rollout percentage
        if ($this->rollout_percentage === 100) {
            return true;
        }

        if ($this->rollout_percentage === 0) {
            return false;
        }

        // Percentage-based rollout using user ID hash
        if ($userId) {
            $hash = crc32($this->key . $userId);
            return ($hash % 100) < $this->rollout_percentage;
        }

        return false;
    }

    public static function isEnabled(string $key, $userId = null): bool
    {
        $flag = static::where('key', $key)->first();
        
        if (!$flag) {
            return false;
        }

        return $flag->isEnabledFor($userId);
    }
}
