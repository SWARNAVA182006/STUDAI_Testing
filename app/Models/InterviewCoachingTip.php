<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterviewCoachingTip extends Model
{
    use HasFactory;

    protected $fillable = [
        'interview_session_id',
        'company_name',
        'role_title',
        'company_talking_points',
        'role_specific_tips',
        'interviewer_insights',
        'cultural_alignment_points',
        'technical_prep_areas',
        'common_mistakes',
        'success_strategies',
    ];

    protected $casts = [
        'company_talking_points' => 'array',
        'role_specific_tips' => 'array',
        'interviewer_insights' => 'array',
        'cultural_alignment_points' => 'array',
        'technical_prep_areas' => 'array',
        'common_mistakes' => 'array',
        'success_strategies' => 'array',
    ];

    // Relationships
    public function interviewSession(): BelongsTo
    {
        return $this->belongsTo(InterviewSession::class);
    }

    // Business Logic
    public function getTalkingPoints(int $limit = 5): array
    {
        return array_slice($this->company_talking_points ?? [], 0, $limit);
    }

    public function getTopMistakes(int $limit = 3): array
    {
        return array_slice($this->common_mistakes ?? [], 0, $limit);
    }

    public function getSuccessStrategies(): array
    {
        return $this->success_strategies ?? [];
    }
}
