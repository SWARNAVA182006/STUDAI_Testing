<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class VideoInterviewTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'created_by',
        'name',
        'description',
        'type',
        'role_category',
        'experience_level',
        'questions',
        'settings',
        'is_public',
        'is_active',
        'usage_count',
    ];

    protected $casts = [
        'questions' => 'array',
        'settings' => 'array',
        'is_public' => 'boolean',
        'is_active' => 'boolean',
        'usage_count' => 'integer',
    ];

    // Types
    public const TYPE_BEHAVIORAL = 'behavioral';
    public const TYPE_TECHNICAL = 'technical';
    public const TYPE_MIXED = 'mixed';
    public const TYPE_CUSTOM = 'custom';

    public const TYPES = [
        self::TYPE_BEHAVIORAL => 'Behavioral',
        self::TYPE_TECHNICAL => 'Technical',
        self::TYPE_MIXED => 'Mixed',
        self::TYPE_CUSTOM => 'Custom',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // Accessors
    public function getQuestionCountAttribute(): int
    {
        return count($this->questions ?? []);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getEstimatedDurationAttribute(): int
    {
        $questions = $this->questions ?? [];
        $totalSeconds = 0;

        foreach ($questions as $question) {
            $prepTime = $question['prep_time_seconds'] ?? 30;
            $responseTime = $question['max_response_time_seconds'] ?? 180;
            $totalSeconds += $prepTime + $responseTime;
        }

        return (int) ceil($totalSeconds / 60);
    }

    // Methods
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    public function createSession(User $user, ?int $jobId = null): VideoInterviewSession
    {
        $this->incrementUsage();

        $session = VideoInterviewSession::create([
            'user_id' => $user->id,
            'job_id' => $jobId,
            'company_id' => $this->company_id,
            'title' => $this->name,
            'description' => $this->description,
            'type' => VideoInterviewSession::TYPE_ASYNC,
            'status' => VideoInterviewSession::STATUS_PENDING,
            'settings' => $this->settings,
            'allow_retakes' => $this->settings['allow_retakes'] ?? false,
            'max_retakes' => $this->settings['max_retakes'] ?? 1,
        ]);

        // Create questions
        foreach ($this->questions as $index => $questionData) {
            VideoInterviewQuestion::create([
                'video_interview_session_id' => $session->id,
                'order' => $index + 1,
                'question_text' => $questionData['question_text'],
                'question_context' => $questionData['question_context'] ?? null,
                'question_type' => $questionData['question_type'] ?? 'general',
                'prep_time_seconds' => $questionData['prep_time_seconds'] ?? 30,
                'max_response_time_seconds' => $questionData['max_response_time_seconds'] ?? 180,
                'min_response_time_seconds' => $questionData['min_response_time_seconds'] ?? 30,
                'max_retakes' => $questionData['max_retakes'] ?? 2,
                'allow_skip' => $questionData['allow_skip'] ?? false,
                'expected_elements' => $questionData['expected_elements'] ?? null,
                'keywords_to_look_for' => $questionData['keywords_to_look_for'] ?? null,
            ]);
        }

        return $session;
    }

    public static function getDefaultQuestions(): array
    {
        return [
            [
                'question_text' => 'Tell me about yourself and your professional background.',
                'question_type' => 'general',
                'prep_time_seconds' => 30,
                'max_response_time_seconds' => 120,
                'expected_elements' => ['career progression', 'key achievements', 'relevant experience'],
            ],
            [
                'question_text' => 'What interests you about this role and our company?',
                'question_type' => 'behavioral',
                'prep_time_seconds' => 30,
                'max_response_time_seconds' => 120,
                'expected_elements' => ['company research', 'role alignment', 'motivation'],
            ],
            [
                'question_text' => 'Describe a challenging situation you faced at work and how you handled it.',
                'question_type' => 'behavioral',
                'prep_time_seconds' => 45,
                'max_response_time_seconds' => 180,
                'expected_elements' => ['situation', 'task', 'action', 'result'],
            ],
            [
                'question_text' => 'Where do you see yourself in 5 years?',
                'question_type' => 'general',
                'prep_time_seconds' => 30,
                'max_response_time_seconds' => 90,
                'expected_elements' => ['career goals', 'growth mindset', 'company alignment'],
            ],
            [
                'question_text' => 'Do you have any questions for us?',
                'question_type' => 'general',
                'prep_time_seconds' => 30,
                'max_response_time_seconds' => 120,
                'expected_elements' => ['thoughtful questions', 'curiosity', 'engagement'],
            ],
        ];
    }
}
