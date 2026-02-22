<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NegotiationMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'message_type',
        'content',
        'metadata',
        'suggestion_category',
        'urgency',
        'confidence_score',
        'in_response_to',
        'suggested_responses',
        'context_analysis',
        'was_helpful',
        'was_used',
        'used_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'suggested_responses' => 'array',
        'context_analysis' => 'array',
        'was_helpful' => 'boolean',
        'was_used' => 'boolean',
        'used_at' => 'datetime',
    ];

    // Relationships
    public function session(): BelongsTo
    {
        return $this->belongsTo(NegotiationSession::class, 'session_id');
    }

    public function inResponseTo(): BelongsTo
    {
        return $this->belongsTo(NegotiationMessage::class, 'in_response_to');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(NegotiationMessage::class, 'in_response_to');
    }

    // Scopes
    public function scopeUserInput($query)
    {
        return $query->where('message_type', 'user_input');
    }

    public function scopeEmployerResponse($query)
    {
        return $query->where('message_type', 'employer_response');
    }

    public function scopeAiSuggestion($query)
    {
        return $query->where('message_type', 'ai_suggestion');
    }

    public function scopeAiAnalysis($query)
    {
        return $query->where('message_type', 'ai_analysis');
    }

    public function scopeSystemNote($query)
    {
        return $query->where('message_type', 'system_note');
    }

    public function scopeByUrgency($query, string $urgency)
    {
        return $query->where('urgency', $urgency);
    }

    public function scopeCritical($query)
    {
        return $query->where('urgency', 'critical');
    }

    public function scopeHighUrgency($query)
    {
        return $query->whereIn('urgency', ['high', 'critical']);
    }

    public function scopeUsed($query)
    {
        return $query->where('was_used', true);
    }

    public function scopeHelpful($query)
    {
        return $query->where('was_helpful', true);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // Accessors
    public function getMessageTypeLabelAttribute(): string
    {
        return match($this->message_type) {
            'user_input' => 'Your Message',
            'employer_response' => 'Employer Response',
            'ai_suggestion' => 'AI Suggestion',
            'ai_analysis' => 'AI Analysis',
            'system_note' => 'System Note',
            default => 'Unknown',
        };
    }

    public function getMessageTypeIconAttribute(): string
    {
        return match($this->message_type) {
            'user_input' => '💬',
            'employer_response' => '🏢',
            'ai_suggestion' => '🤖',
            'ai_analysis' => '📊',
            'system_note' => '📝',
            default => '•',
        };
    }

    public function getMessageTypeColorAttribute(): string
    {
        return match($this->message_type) {
            'user_input' => 'blue',
            'employer_response' => 'purple',
            'ai_suggestion' => 'green',
            'ai_analysis' => 'yellow',
            'system_note' => 'gray',
            default => 'gray',
        };
    }

    public function getSuggestionCategoryLabelAttribute(): ?string
    {
        if (!$this->suggestion_category) {
            return null;
        }

        return match($this->suggestion_category) {
            'response_suggestion' => 'Response Suggestion',
            'tactic_recommendation' => 'Tactic Recommendation',
            'warning' => 'Warning',
            'encouragement' => 'Encouragement',
            'data_point' => 'Data Point',
            'pivot_suggestion' => 'Pivot Suggestion',
            'closing_advice' => 'Closing Advice',
            default => 'Suggestion',
        };
    }

    public function getUrgencyLabelAttribute(): ?string
    {
        if (!$this->urgency) {
            return null;
        }

        return match($this->urgency) {
            'low' => 'Low Priority',
            'medium' => 'Medium Priority',
            'high' => 'High Priority',
            'critical' => 'Critical',
            default => 'Normal',
        };
    }

    public function getUrgencyColorAttribute(): ?string
    {
        if (!$this->urgency) {
            return null;
        }

        return match($this->urgency) {
            'low' => 'gray',
            'medium' => 'blue',
            'high' => 'orange',
            'critical' => 'red',
            default => 'gray',
        };
    }

    public function getUrgencyIconAttribute(): ?string
    {
        if (!$this->urgency) {
            return null;
        }

        return match($this->urgency) {
            'low' => '🔵',
            'medium' => '🟡',
            'high' => '🟠',
            'critical' => '🔴',
            default => '⚪',
        };
    }

    public function getConfidenceLevelAttribute(): ?string
    {
        if (!$this->confidence_score) {
            return null;
        }

        return match(true) {
            $this->confidence_score >= 80 => 'very_high',
            $this->confidence_score >= 60 => 'high',
            $this->confidence_score >= 40 => 'medium',
            default => 'low',
        };
    }

    public function getConfidenceLevelColorAttribute(): ?string
    {
        if (!$this->confidence_score) {
            return null;
        }

        return match($this->confidence_level) {
            'very_high' => 'green',
            'high' => 'blue',
            'medium' => 'yellow',
            'low' => 'red',
            default => 'gray',
        };
    }

    // Helper Methods
    public function isFromUser(): bool
    {
        return $this->message_type === 'user_input';
    }

    public function isFromEmployer(): bool
    {
        return $this->message_type === 'employer_response';
    }

    public function isAiSuggestion(): bool
    {
        return $this->message_type === 'ai_suggestion';
    }

    public function isAiAnalysis(): bool
    {
        return $this->message_type === 'ai_analysis';
    }

    public function isCritical(): bool
    {
        return $this->urgency === 'critical';
    }

    public function isHighPriority(): bool
    {
        return in_array($this->urgency, ['high', 'critical']);
    }

    public function hasHighConfidence(): bool
    {
        return $this->confidence_score && $this->confidence_score >= 70;
    }

    public function hasSuggestedResponses(): bool
    {
        return !empty($this->suggested_responses);
    }

    public function hasContextAnalysis(): bool
    {
        return !empty($this->context_analysis);
    }

    public function markAsUsed(): void
    {
        $this->update([
            'was_used' => true,
            'used_at' => now(),
        ]);
    }

    public function markAsHelpful(bool $helpful = true): void
    {
        $this->update(['was_helpful' => $helpful]);
    }

    public function getSuggestedResponsesFormatted(): array
    {
        if (!$this->hasSuggestedResponses()) {
            return [];
        }

        return array_map(function($response, $index) {
            return [
                'id' => $index + 1,
                'text' => is_array($response) ? ($response['text'] ?? $response) : $response,
                'tone' => is_array($response) ? ($response['tone'] ?? 'professional') : 'professional',
                'risk' => is_array($response) ? ($response['risk'] ?? 'medium') : 'medium',
            ];
        }, $this->suggested_responses, array_keys($this->suggested_responses));
    }

    public function getContextSummary(): ?string
    {
        if (!$this->hasContextAnalysis()) {
            return null;
        }

        $analysis = $this->context_analysis;

        $summary = [];

        if (isset($analysis['sentiment'])) {
            $summary[] = "Sentiment: {$analysis['sentiment']}";
        }

        if (isset($analysis['key_points'])) {
            $points = is_array($analysis['key_points']) ? 
                implode(', ', $analysis['key_points']) : 
                $analysis['key_points'];
            $summary[] = "Key Points: {$points}";
        }

        if (isset($analysis['recommendation'])) {
            $summary[] = "Recommendation: {$analysis['recommendation']}";
        }

        return implode(' | ', $summary);
    }

    public function getMetadataSummary(): ?string
    {
        if (empty($this->metadata)) {
            return null;
        }

        $summary = [];

        foreach ($this->metadata as $key => $value) {
            if (is_array($value)) {
                $summary[] = ucfirst($key) . ': ' . implode(', ', $value);
            } else {
                $summary[] = ucfirst($key) . ': ' . $value;
            }
        }

        return implode(' | ', $summary);
    }

    public function getConversationThread(): array
    {
        $thread = [];

        // Get the root message
        $current = $this;
        while ($current->in_response_to) {
            $parent = $current->inResponseTo;
            if (!$parent) break;
            
            array_unshift($thread, [
                'id' => $parent->id,
                'type' => $parent->message_type,
                'content' => $parent->content,
                'created_at' => $parent->created_at->format('Y-m-d H:i:s'),
            ]);

            $current = $parent;
        }

        // Add this message
        $thread[] = [
            'id' => $this->id,
            'type' => $this->message_type,
            'content' => $this->content,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];

        // Add responses
        foreach ($this->responses as $response) {
            $thread[] = [
                'id' => $response->id,
                'type' => $response->message_type,
                'content' => $response->content,
                'created_at' => $response->created_at->format('Y-m-d H:i:s'),
            ];
        }

        return $thread;
    }

    public function analyzeEmployerTone(): ?array
    {
        if (!$this->isFromEmployer()) {
            return null;
        }

        $content = strtolower($this->content);
        
        $toneIndicators = [
            'positive' => ['excited', 'great', 'excellent', 'love', 'impressed', 'perfect', 'wonderful'],
            'neutral' => ['consider', 'review', 'discuss', 'understand', 'appreciate'],
            'negative' => ['unfortunately', 'however', 'concerned', 'limited', 'constrained', 'cannot'],
            'receptive' => ['open to', 'flexible', 'willing to', 'can work with', 'possible'],
            'resistant' => ['not possible', 'unable to', 'fixed', 'non-negotiable', 'final'],
        ];

        $tones = [];
        foreach ($toneIndicators as $tone => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($content, $keyword) !== false) {
                    $tones[$tone] = ($tones[$tone] ?? 0) + 1;
                }
            }
        }

        if (empty($tones)) {
            return null;
        }

        arsort($tones);

        return [
            'primary_tone' => array_key_first($tones),
            'detected_tones' => $tones,
            'is_positive' => isset($tones['positive']) || isset($tones['receptive']),
            'is_negative' => isset($tones['negative']) || isset($tones['resistant']),
        ];
    }

    public function extractKeyPhrases(): array
    {
        $content = $this->content;
        
        // Extract phrases in quotes
        preg_match_all('/"([^"]+)"/', $content, $quoted);
        
        // Extract dollar amounts
        preg_match_all('/\$[\d,]+/', $content, $amounts);
        
        // Extract percentages
        preg_match_all('/\d+%/', $content, $percentages);
        
        return [
            'quoted_phrases' => $quoted[1] ?? [],
            'amounts' => $amounts[0] ?? [],
            'percentages' => $percentages[0] ?? [],
        ];
    }
}
