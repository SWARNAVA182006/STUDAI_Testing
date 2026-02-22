<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailTemplateAnalytics extends Model
{
    use HasFactory;

    protected $table = 'email_template_analytics';

    protected $fillable = [
        'template_id',
        'date',
        'sends',
        'deliveries',
        'opens',
        'unique_opens',
        'clicks',
        'unique_clicks',
        'bounces',
        'failures',
        'open_rate',
        'click_rate',
    ];

    protected $casts = [
        'date' => 'date',
        'sends' => 'integer',
        'deliveries' => 'integer',
        'opens' => 'integer',
        'unique_opens' => 'integer',
        'clicks' => 'integer',
        'unique_clicks' => 'integer',
        'bounces' => 'integer',
        'failures' => 'integer',
        'open_rate' => 'decimal:2',
        'click_rate' => 'decimal:2',
    ];

    /**
     * Get the template.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'template_id');
    }

    /**
     * Calculate and update rates.
     */
    public function calculateRates(): void
    {
        $this->open_rate = $this->deliveries > 0
            ? round(($this->unique_opens / $this->deliveries) * 100, 2)
            : 0;

        $this->click_rate = $this->unique_opens > 0
            ? round(($this->unique_clicks / $this->unique_opens) * 100, 2)
            : 0;

        $this->save();
    }
}
