<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RolePrediction extends Model
{
    use HasFactory;

    protected $fillable = [
        'role_title',
        'industry',
        'location',
        'current_demand_score',
        'current_job_count',
        'current_avg_salary',
        'predicted_demand_3m',
        'predicted_demand_6m',
        'predicted_demand_12m',
        'predicted_salary_change',
        'role_status',
        'emergence_score',
        'stability_score',
        'ai_rationale',
        'key_drivers',
        'required_skills',
        'similar_roles',
        'hiring_velocity',
        'competition_level',
        'recommendation',
        'confidence_score',
        'prediction_date',
    ];

    protected $casts = [
        'current_demand_score' => 'integer',
        'current_job_count' => 'integer',
        'current_avg_salary' => 'decimal:2',
        'predicted_demand_3m' => 'integer',
        'predicted_demand_6m' => 'integer',
        'predicted_demand_12m' => 'integer',
        'predicted_salary_change' => 'decimal:2',
        'emergence_score' => 'integer',
        'stability_score' => 'integer',
        'key_drivers' => 'array',
        'required_skills' => 'array',
        'similar_roles' => 'array',
        'hiring_velocity' => 'integer',
        'competition_level' => 'decimal:2',
        'confidence_score' => 'decimal:2',
        'prediction_date' => 'date',
    ];

    /**
     * Get latest prediction for role
     */
    public static function getLatest(string $roleTitle): ?self
    {
        return static::where('role_title', $roleTitle)
            ->latest('prediction_date')
            ->first();
    }

    /**
     * Get emerging roles (high emergence score)
     */
    public static function getEmerging(int $limit = 20): \Illuminate\Support\Collection
    {
        return static::where('role_status', 'emerging')
            ->where('prediction_date', '>=', now()->subDays(30))
            ->orderByDesc('emergence_score')
            ->limit($limit)
            ->get();
    }

    /**
     * Get declining roles (to avoid)
     */
    public static function getDeclining(int $limit = 20): \Illuminate\Support\Collection
    {
        return static::whereIn('role_status', ['declining', 'obsolete'])
            ->where('prediction_date', '>=', now()->subDays(30))
            ->orderBy('stability_score')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recommended roles
     */
    public static function getRecommended(int $limit = 20): \Illuminate\Support\Collection
    {
        return static::where('recommendation', 'pursue')
            ->where('prediction_date', '>=', now()->subDays(30))
            ->orderByDesc('current_demand_score')
            ->limit($limit)
            ->get();
    }

    /**
     * Get role status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->role_status) {
            'emerging' => 'blue',
            'growing' => 'green',
            'stable' => 'gray',
            'declining' => 'yellow',
            'obsolete' => 'red',
            default => 'gray',
        };
    }

    /**
     * Get recommendation badge color
     */
    public function getRecommendationColorAttribute(): string
    {
        return match($this->recommendation) {
            'pursue' => 'green',
            'consider' => 'yellow',
            'avoid' => 'red',
            default => 'gray',
        };
    }
}
