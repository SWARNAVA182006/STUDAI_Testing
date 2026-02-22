<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferLetterActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'offer_letter_id',
        'user_id',
        'action',
        'description',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function offerLetter(): BelongsTo
    {
        return $this->belongsTo(OfferLetter::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getIconAttribute(): string
    {
        return match($this->action) {
            'created' => 'heroicon-o-document-plus',
            'updated' => 'heroicon-o-pencil',
            'sent' => 'heroicon-o-paper-airplane',
            'viewed' => 'heroicon-o-eye',
            'accepted' => 'heroicon-o-check-circle',
            'declined' => 'heroicon-o-x-circle',
            'counter_offered' => 'heroicon-o-arrow-path',
            'counter_offer_accepted' => 'heroicon-o-check',
            'counter_offer_partially_accepted' => 'heroicon-o-adjustments-horizontal',
            'counter_offer_rejected' => 'heroicon-o-x-mark',
            'withdrawn' => 'heroicon-o-archive-box-x-mark',
            'expired' => 'heroicon-o-clock',
            'signed' => 'heroicon-o-pencil-square',
            'signature_requested' => 'heroicon-o-document-check',
            default => 'heroicon-o-information-circle',
        };
    }

    public function getColorAttribute(): string
    {
        return match($this->action) {
            'accepted', 'counter_offer_accepted', 'signed' => 'success',
            'declined', 'counter_offer_rejected', 'withdrawn', 'expired' => 'danger',
            'counter_offered', 'counter_offer_partially_accepted' => 'warning',
            'sent', 'viewed', 'signature_requested' => 'info',
            default => 'gray',
        };
    }

    public function scopeRecent($query, int $limit = 10)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    public function scopeForAction($query, string $action)
    {
        return $query->where('action', $action);
    }
}
