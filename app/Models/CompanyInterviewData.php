<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyInterviewData extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'role_title',
        'department',
        'interview_type',
        'common_questions',
        'interviewer_profiles',
        'interview_structure',
        'difficulty_ratings',
        'success_patterns',
        'cultural_values',
        'technical_focus_areas',
        'notes',
        'data_points_count',
        'last_updated_at',
    ];

    protected $casts = [
        'common_questions' => 'array',
        'interviewer_profiles' => 'array',
        'interview_structure' => 'array',
        'difficulty_ratings' => 'array',
        'success_patterns' => 'array',
        'cultural_values' => 'array',
        'technical_focus_areas' => 'array',
        'last_updated_at' => 'datetime',
    ];

    // Scopes
    public function scopeForCompany($query, string $companyName)
    {
        return $query->where('company_name', $companyName);
    }

    public function scopeForRole($query, string $roleTitle)
    {
        return $query->where('role_title', $roleTitle);
    }

    public function scopeByInterviewType($query, string $type)
    {
        return $query->where('interview_type', $type);
    }

    public function scopeRecent($query, int $days = 90)
    {
        return $query->where('last_updated_at', '>=', now()->subDays($days));
    }

    // Business Logic
    public function hasSubstantialData(): bool
    {
        return $this->data_points_count >= 10;
    }

    public function getCommonQuestionsByCategory(string $category = null): array
    {
        $questions = $this->common_questions ?? [];

        if ($category) {
            return array_filter($questions, function($q) use ($category) {
                return ($q['category'] ?? '') === $category;
            });
        }

        return $questions;
    }

    public function getCulturalKeywords(): array
    {
        return $this->cultural_values ?? [];
    }

    public function getTechnicalTopics(): array
    {
        return $this->technical_focus_areas ?? [];
    }

    public function getInterviewerProfiles(): array
    {
        return $this->interviewer_profiles ?? [];
    }

    public function updateDataPoints(int $additionalPoints = 1): void
    {
        $this->increment('data_points_count', $additionalPoints);
        $this->update(['last_updated_at' => now()]);
    }
}
