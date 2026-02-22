<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailTemplateVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_id',
        'user_id',
        'version_number',
        'subject',
        'body_html',
        'body_text',
        'change_notes',
    ];

    protected $casts = [
        'version_number' => 'integer',
    ];

    /**
     * Get the template.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'template_id');
    }

    /**
     * Get the user who created this version.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
