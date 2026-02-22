<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicationTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'content',
        'variables',
        'target_roles',
        'times_used',
        'success_rate',
        'average_match_score',
        'is_default',
    ];

    protected $casts = [
        'variables' => 'array',
        'target_roles' => 'array',
        'times_used' => 'integer',
        'success_rate' => 'float',
        'average_match_score' => 'float',
        'is_default' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeCoverLetters($query)
    {
        return $query->where('type', 'cover_letter');
    }

    public function scopeResumes($query)
    {
        return $query->where('type', 'resume');
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function render(array $data): string
    {
        $content = $this->content;

        foreach ($data as $key => $value) {
            $content = str_replace("{{$key}}", $value, $content);
        }

        return $content;
    }

    public function incrementUsage(): void
    {
        $this->increment('times_used');
    }

    public function updatePerformance(float $matchScore, bool $gotResponse): void
    {
        // Update average match score
        if ($this->times_used > 0) {
            $this->average_match_score = (
                ($this->average_match_score * $this->times_used) + $matchScore
            ) / ($this->times_used + 1);
        } else {
            $this->average_match_score = $matchScore;
        }

        // Update success rate
        if ($gotResponse) {
            $successCount = $this->times_used * ($this->success_rate / 100);
            $this->success_rate = (($successCount + 1) / ($this->times_used + 1)) * 100;
        }

        $this->save();
    }
}
