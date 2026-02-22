<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SkillAssessment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'user_skill_id', 'skill_name', 'assessment_title', 'description',
        'assessment_type', 'difficulty_level', 'questions', 'total_questions',
        'passing_score', 'time_limit_minutes', 'answers', 'score', 'passed',
        'proficiency_awarded', 'detailed_results', 'strengths', 'weaknesses',
        'recommendations', 'started_at', 'submitted_at', 'expires_at',
        'is_shareable', 'certificate_url', 'certificate_hash', 'status',
    ];

    protected $casts = [
        'questions' => 'array', 'answers' => 'array', 'detailed_results' => 'array',
        'strengths' => 'array', 'weaknesses' => 'array', 'recommendations' => 'array',
        'passed' => 'boolean', 'is_shareable' => 'boolean',
        'started_at' => 'datetime', 'submitted_at' => 'datetime', 'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function userSkill(): BelongsTo { return $this->belongsTo(UserSkill::class); }

    public function scopeByStatus($query, string $status) { return $query->where('status', $status); }
    public function scopePassed($query) { return $query->where('passed', true); }
    public function scopeFailed($query) { return $query->where('passed', false)->whereNotNull('passed'); }
    public function scopeActive($query) { return $query->whereIn('status', ['draft', 'in_progress']); }

    public function getTypeBadgeAttribute(): string {
        return match($this->assessment_type) {
            'multiple_choice' => '✅ Multiple Choice',
            'coding' => '💻 Coding',
            'scenario_based' => '🎯 Scenario',
            'project' => '🏗️ Project',
            'mixed' => '🔀 Mixed',
            default => '📝 Assessment'
        };
    }

    public function getScorePercentageAttribute(): ?float { return $this->score; }
    public function getGradeAttribute(): string {
        if (!$this->score) return 'N/A';
        if ($this->score >= 90) return 'A';
        if ($this->score >= 80) return 'B';
        if ($this->score >= 70) return 'C';
        if ($this->score >= 60) return 'D';
        return 'F';
    }

    public function start(): void {
        $this->update(['status' => 'in_progress', 'started_at' => now()]);
    }

    public function submit(array $answers): void {
        $this->update([
            'answers' => $answers,
            'submitted_at' => now(),
            'status' => 'submitted',
        ]);
    }

    public function grade(int $score, array $results): void {
        $passed = $score >= $this->passing_score;
        $proficiency = $this->determineProficiency($score);
        
        $this->update([
            'score' => $score,
            'passed' => $passed,
            'proficiency_awarded' => $proficiency,
            'detailed_results' => $results,
            'status' => 'graded',
        ]);
        
        if ($passed) {
            $this->generateCertificate();
        }
    }

    public function generateCertificate(): void {
        $hash = Str::random(32);
        $this->update([
            'certificate_hash' => $hash,
            'certificate_url' => route('skills.certificate', $hash),
            'is_shareable' => true,
            'expires_at' => now()->addYear(),
        ]);
    }

    protected function determineProficiency(int $score): string {
        if ($score >= 95) return 'expert';
        if ($score >= 85) return 'advanced';
        if ($score >= 70) return 'intermediate';
        return 'beginner';
    }

    public static function validationRules(): array {
        return [
            'skill_name' => 'required|string|max:255',
            'assessment_title' => 'required|string|max:255',
            'assessment_type' => 'required|in:multiple_choice,coding,scenario_based,project,mixed',
            'difficulty_level' => 'required|in:beginner,intermediate,advanced,expert',
            'passing_score' => 'required|integer|min:0|max:100',
        ];
    }
}
