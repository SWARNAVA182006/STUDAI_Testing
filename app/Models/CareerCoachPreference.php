<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareerCoachPreference extends Model
{
    use HasFactory;

    // Coaching Styles
    public const STYLE_SUPPORTIVE = 'supportive';
    public const STYLE_DIRECT = 'direct';
    public const STYLE_ANALYTICAL = 'analytical';
    public const STYLE_MOTIVATIONAL = 'motivational';

    // Suggestion Frequencies
    public const FREQUENCY_DAILY = 'daily';
    public const FREQUENCY_WEEKLY = 'weekly';
    public const FREQUENCY_OCCASIONAL = 'occasional';

    protected $fillable = [
        'user_id',
        'weekly_checkins_enabled',
        'preferred_checkin_day',
        'preferred_checkin_time',
        'timezone',
        'proactive_suggestions_enabled',
        'suggestion_frequency',
        'voice_enabled',
        'preferred_language',
        'coaching_style',
        'focus_areas',
        'email_notifications',
        'push_notifications',
    ];

    protected $casts = [
        'weekly_checkins_enabled' => 'boolean',
        'proactive_suggestions_enabled' => 'boolean',
        'voice_enabled' => 'boolean',
        'focus_areas' => 'array',
        'email_notifications' => 'boolean',
        'push_notifications' => 'boolean',
    ];

    /**
     * Get the user that owns the preferences.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get coaching style labels.
     */
    public static function getStyleLabels(): array
    {
        return [
            self::STYLE_SUPPORTIVE => 'Supportive & Encouraging',
            self::STYLE_DIRECT => 'Direct & Actionable',
            self::STYLE_ANALYTICAL => 'Analytical & Data-Driven',
            self::STYLE_MOTIVATIONAL => 'Motivational & Inspiring',
        ];
    }

    /**
     * Get frequency labels.
     */
    public static function getFrequencyLabels(): array
    {
        return [
            self::FREQUENCY_DAILY => 'Daily',
            self::FREQUENCY_WEEKLY => 'Weekly',
            self::FREQUENCY_OCCASIONAL => 'Occasional',
        ];
    }

    /**
     * Get day of week options.
     */
    public static function getDayOptions(): array
    {
        return [
            'monday' => 'Monday',
            'tuesday' => 'Tuesday',
            'wednesday' => 'Wednesday',
            'thursday' => 'Thursday',
            'friday' => 'Friday',
            'saturday' => 'Saturday',
            'sunday' => 'Sunday',
        ];
    }

    /**
     * Get the style label.
     */
    public function getStyleLabel(): string
    {
        return self::getStyleLabels()[$this->coaching_style] ?? $this->coaching_style;
    }

    /**
     * Get system prompt based on coaching style.
     */
    public function getSystemPromptStyle(): string
    {
        return match ($this->coaching_style) {
            self::STYLE_SUPPORTIVE => 'Be warm, encouraging, and supportive. Celebrate wins and provide gentle guidance. Use phrases like "You\'re doing great" and "I believe in you".',
            self::STYLE_DIRECT => 'Be direct and action-oriented. Focus on clear next steps and measurable outcomes. Avoid fluff and get straight to the point.',
            self::STYLE_ANALYTICAL => 'Use data, statistics, and logical reasoning. Provide evidence-based recommendations. Break down complex decisions into pros and cons.',
            self::STYLE_MOTIVATIONAL => 'Be energetic and inspiring. Use motivational language and success stories. Help the user visualize their success and push through challenges.',
            default => 'Be professional, helpful, and balanced in your approach.',
        };
    }

    /**
     * Get or create preferences for a user.
     */
    public static function getOrCreate(User $user): self
    {
        return self::firstOrCreate(
            ['user_id' => $user->id],
            [
                'weekly_checkins_enabled' => true,
                'preferred_checkin_day' => 'monday',
                'preferred_checkin_time' => '09:00',
                'timezone' => 'Asia/Kolkata',
                'proactive_suggestions_enabled' => true,
                'suggestion_frequency' => self::FREQUENCY_WEEKLY,
                'voice_enabled' => false,
                'preferred_language' => 'en',
                'coaching_style' => self::STYLE_SUPPORTIVE,
                'email_notifications' => true,
                'push_notifications' => true,
            ]
        );
    }
}
