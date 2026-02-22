<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterviewExperienceVote extends Model
{
    protected $fillable = [
        'interview_experience_id',
        'user_id',
        'is_helpful',
    ];

    protected $casts = [
        'is_helpful' => 'boolean',
    ];

    public function experience(): BelongsTo
    {
        return $this->belongsTo(InterviewExperience::class, 'interview_experience_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
