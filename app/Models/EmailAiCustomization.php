<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailAiCustomization extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_id',
        'user_id',
        'original_content',
        'customized_content',
        'customization_params',
        'prompt_used',
        'tokens_used',
        'was_accepted',
    ];

    protected $casts = [
        'customization_params' => 'array',
        'tokens_used' => 'integer',
        'was_accepted' => 'boolean',
    ];

    /**
     * Get the template.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'template_id');
    }

    /**
     * Get the user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mark as accepted.
     */
    public function markAsAccepted(): void
    {
        $this->update(['was_accepted' => true]);
    }
}
