<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkillValidation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'user_skill_id', 'skill_name', 'validation_source',
        'evidence_description', 'evidence_data', 'confidence_score',
        'proficiency_detected', 'years_of_experience', 'key_achievements',
        'projects', 'ai_analysis', 'demonstration_suggestions',
        'is_verified', 'verified_at',
    ];

    protected $casts = [
        'evidence_data' => 'array',
        'key_achievements' => 'array',
        'projects' => 'array',
        'ai_analysis' => 'array',
        'demonstration_suggestions' => 'array',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function userSkill(): BelongsTo { return $this->belongsTo(UserSkill::class); }

    public function scopeVerified($query) { return $query->where('is_verified', true); }
    public function scopeBySource($query, string $source) { return $query->where('validation_source', $source); }
    public function scopeHighConfidence($query, int $threshold = 80) { return $query->where('confidence_score', '>=', $threshold); }

    public function getSourceBadgeAttribute(): string {
        return match($this->validation_source) {
            'work_history' => '💼 Work History',
            'project' => '🎯 Project',
            'education' => '🎓 Education',
            'certification' => '📜 Certification',
            'endorsement' => '👍 Endorsement',
            'assessment' => '✅ Assessment',
            default => '📋 Validation'
        };
    }

    public function getConfidenceLevelAttribute(): string {
        if ($this->confidence_score >= 90) return 'Very High';
        if ($this->confidence_score >= 75) return 'High';
        if ($this->confidence_score >= 60) return 'Moderate';
        if ($this->confidence_score >= 40) return 'Low';
        return 'Very Low';
    }

    public function verify(): void {
        $this->update(['is_verified' => true, 'verified_at' => now()]);
        
        if ($this->userSkill) {
            $this->userSkill->markAsVerified('validation');
        }
    }

    public static function validationRules(): array {
        return [
            'skill_name' => 'required|string|max:255',
            'validation_source' => 'required|in:work_history,project,education,certification,endorsement,assessment',
            'confidence_score' => 'required|integer|min:0|max:100',
        ];
    }
}
