<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceTimeLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'freelancer_id',
        'work_date',
        'start_time',
        'end_time',
        'minutes_worked',
        'hourly_rate',
        'amount_earned',
        'description',
        'screenshots',
        'status',
        'approved_at',
        'paid_at',
    ];

    protected $casts = [
        'work_date' => 'date',
        'hourly_rate' => 'decimal:2',
        'amount_earned' => 'decimal:2',
        'screenshots' => 'array',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    // Relationships
    public function contract(): BelongsTo
    {
        return $this->belongsTo(MarketplaceContract::class, 'contract_id');
    }

    public function freelancer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'freelancer_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeForWeek($query, $date)
    {
        return $query->whereBetween('work_date', [
            $date->startOfWeek(),
            $date->endOfWeek(),
        ]);
    }

    // Helpers
    public function getHoursWorkedAttribute(): float
    {
        return round($this->minutes_worked / 60, 2);
    }

    public function getAmountDisplayAttribute(): string
    {
        return sprintf('%s %s', 
            $this->contract->currency ?? 'INR', 
            number_format($this->amount_earned)
        );
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function approve(): void
    {
        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    public function dispute(): void
    {
        $this->update(['status' => 'disputed']);
    }

    public function markPaid(): void
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($log) {
            if (empty($log->amount_earned)) {
                $log->amount_earned = ($log->minutes_worked / 60) * $log->hourly_rate;
            }
        });
    }
}
