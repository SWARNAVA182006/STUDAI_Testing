<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchedulingLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'slug',
        'title',
        'description',
        'duration_minutes',
        'buffer_before',
        'buffer_after',
        'min_notice_hours',
        'max_days_ahead',
        'available_days',
        'questions',
        'require_confirmation',
        'meeting_type',
        'meeting_provider',
        'is_active',
        'bookings_count',
    ];

    protected $casts = [
        'available_days' => 'array',
        'questions' => 'array',
        'require_confirmation' => 'boolean',
        'is_active' => 'boolean',
        'duration_minutes' => 'integer',
        'buffer_before' => 'integer',
        'buffer_after' => 'integer',
        'min_notice_hours' => 'integer',
        'max_days_ahead' => 'integer',
        'bookings_count' => 'integer',
    ];

    /**
     * Get the user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the public booking URL.
     */
    public function getBookingUrlAttribute(): string
    {
        return url('/schedule/' . $this->slug);
    }

    /**
     * Get duration formatted.
     */
    public function getFormattedDurationAttribute(): string
    {
        if ($this->duration_minutes >= 60) {
            $hours = floor($this->duration_minutes / 60);
            $mins = $this->duration_minutes % 60;
            return $hours . ' hr' . ($mins > 0 ? ' ' . $mins . ' min' : '');
        }

        return $this->duration_minutes . ' min';
    }

    /**
     * Increment booking count.
     */
    public function incrementBookings(): void
    {
        $this->increment('bookings_count');
    }

    /**
     * Check if bookings are currently available.
     */
    public function isAvailableForBooking(): bool
    {
        return $this->is_active;
    }

    /**
     * Get the earliest bookable time.
     */
    public function getEarliestBookableTime(): \Carbon\Carbon
    {
        return now()->addHours($this->min_notice_hours);
    }

    /**
     * Get the latest bookable time.
     */
    public function getLatestBookableTime(): \Carbon\Carbon
    {
        return now()->addDays($this->max_days_ahead);
    }

    /**
     * Scope: Active links.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
