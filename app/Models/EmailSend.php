<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailSend extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_id',
        'user_id',
        'company_id',
        'recipient_email',
        'recipient_name',
        'subject',
        'message_id',
        'status',
        'sent_at',
        'delivered_at',
        'opened_at',
        'clicked_at',
        'open_count',
        'click_count',
        'metadata',
        'error_message',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
        'open_count' => 'integer',
        'click_count' => 'integer',
    ];

    /**
     * Get the template.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'template_id');
    }

    /**
     * Get the user who sent the email.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the company.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Mark as sent.
     */
    public function markAsSent(?string $messageId = null): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
            'message_id' => $messageId,
        ]);
    }

    /**
     * Mark as delivered.
     */
    public function markAsDelivered(): void
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
    }

    /**
     * Mark as opened.
     */
    public function markAsOpened(): void
    {
        $this->increment('open_count');
        
        if ($this->status !== 'clicked') {
            $this->update([
                'status' => 'opened',
                'opened_at' => $this->opened_at ?? now(),
            ]);
        }
    }

    /**
     * Mark as clicked.
     */
    public function markAsClicked(): void
    {
        $this->increment('click_count');
        $this->update([
            'status' => 'clicked',
            'clicked_at' => $this->clicked_at ?? now(),
        ]);
    }

    /**
     * Mark as bounced.
     */
    public function markAsBounced(?string $errorMessage = null): void
    {
        $this->update([
            'status' => 'bounced',
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Mark as failed.
     */
    public function markAsFailed(?string $errorMessage = null): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Scope by status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope sent emails.
     */
    public function scopeSent($query)
    {
        return $query->whereIn('status', ['sent', 'delivered', 'opened', 'clicked']);
    }
}
