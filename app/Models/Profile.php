<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $fillable = [
        'user_id',
        'headline',
        'summary',
        'skills',
        'languages',
        'experience',
        'education',
        'certifications',
        'projects',
        'current_location',
        'preferred_locations',
        'expected_salary_min',
        'expected_salary_max',
        'notice_period',
        'work_preference',
        'social_links',
        'profile_completeness',
        'is_public',
        'open_to_opportunities',
        'linkedin_url',
        'portfolio_url',
        'github_url',
    ];

    protected $casts = [
        'skills' => 'array',
        'languages' => 'array',
        'experience' => 'array',
        'education' => 'array',
        'certifications' => 'array',
        'projects' => 'array',
        'preferred_locations' => 'array',
        'social_links' => 'array',
        'expected_salary_min' => 'decimal:2',
        'expected_salary_max' => 'decimal:2',
        'is_public' => 'boolean',
        'open_to_opportunities' => 'boolean',
        'profile_completeness' => 'integer',
    ];

    /**
     * Get the user that owns the profile
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get profile completion percentage
     */
    public function getCompletionPercentage(): int
    {
        $fields = [
            'headline',
            'summary',
            'skills',
            'experience',
            'education',
            'current_location',
            'expected_salary_min',
            'linkedin_url',
        ];

        $completed = 0;
        foreach ($fields as $field) {
            if (!empty($this->$field)) {
                $completed++;
            }
        }

        return round(($completed / count($fields)) * 100);
    }
}

