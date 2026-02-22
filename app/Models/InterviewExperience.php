<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InterviewExperience extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'company_id',
        'job_title',
        'department',
        'location',
        'application_method',
        'interview_date',
        'interview_duration',
        'interview_stages',
        'num_interviews',
        'experience',
        'difficulty',
        'outcome',
        'accepted_offer',
        'offered_salary',
        'currency',
        'interview_process',
        'interview_questions',
        'preparation_tips',
        'advice_for_candidates',
        'is_verified',
        'status',
        'is_anonymous',
        'helpful_count',
        'view_count',
    ];

    protected $casts = [
        'accepted_offer' => 'boolean',
        'is_verified' => 'boolean',
        'is_anonymous' => 'boolean',
        'interview_stages' => 'array',
        'interview_questions' => 'array',
        'interview_date' => 'date',
        'offered_salary' => 'decimal:2',
        'num_interviews' => 'integer',
        'helpful_count' => 'integer',
        'view_count' => 'integer',
    ];

    // Application methods
    public const APPLICATION_METHODS = [
        'online' => 'Online Application',
        'recruiter' => 'Recruiter Contact',
        'referral' => 'Employee Referral',
        'career_fair' => 'Career Fair',
        'campus' => 'Campus Recruiting',
        'other' => 'Other',
    ];

    // Experience types
    public const EXPERIENCE_TYPES = [
        'positive' => 'Positive',
        'neutral' => 'Neutral',
        'negative' => 'Negative',
    ];

    // Difficulty levels
    public const DIFFICULTY_LEVELS = [
        'easy' => 'Easy',
        'average' => 'Average',
        'difficult' => 'Difficult',
        'very_difficult' => 'Very Difficult',
    ];

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopePositive($query)
    {
        return $query->where('experience', 'positive');
    }

    public function scopeWithOffer($query)
    {
        return $query->whereIn('outcome', ['got_offer', 'declined_offer']);
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(InterviewExperienceVote::class);
    }

    // Accessors
    public function getDifficultyLabelAttribute(): string
    {
        return self::DIFFICULTY_LEVELS[$this->difficulty] ?? 'Unknown';
    }

    public function getApplicationMethodLabelAttribute(): string
    {
        return self::APPLICATION_METHODS[$this->application_method] ?? $this->application_method ?? 'Unknown';
    }

    public function getExperienceIconAttribute(): string
    {
        return match ($this->experience) {
            'positive' => '😊',
            'neutral' => '😐',
            'negative' => '😞',
            default => '❓',
        };
    }

    public function getExperienceColorAttribute(): string
    {
        return match ($this->experience) {
            'positive' => 'green',
            'neutral' => 'yellow',
            'negative' => 'red',
            default => 'gray',
        };
    }

    public function getOutcomeTextAttribute(): string
    {
        return match ($this->outcome) {
            'got_offer' => $this->accepted_offer ? 'Received and accepted offer' : 'Received offer',
            'declined_offer' => 'Declined offer',
            'no_offer' => 'Did not receive offer',
            'pending' => 'Pending decision',
            'withdrew' => 'Withdrew application',
            default => 'Unknown',
        };
    }

    public function getOutcomeColorAttribute(): string
    {
        return match ($this->outcome) {
            'got_offer' => 'green',
            'declined_offer' => 'yellow',
            'no_offer' => 'red',
            'pending' => 'blue',
            'withdrew' => 'gray',
            default => 'gray',
        };
    }

    // Methods
    public function incrementView(): void
    {
        $this->increment('view_count');
    }

    public function markHelpful(User $user): void
    {
        $vote = $this->votes()->where('user_id', $user->id)->first();

        if (!$vote) {
            $this->votes()->create([
                'user_id' => $user->id,
                'is_helpful' => true,
            ]);
            $this->increment('helpful_count');
        }
    }

    protected static function booted(): void
    {
        static::created(function (InterviewExperience $experience) {
            if ($experience->status === 'approved') {
                $experience->company->incrementInterviewCount();
            }
        });
    }
}
