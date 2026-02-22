<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyReviewReport extends Model
{
    protected $fillable = [
        'company_review_id',
        'user_id',
        'reason',
        'details',
        'status',
    ];

    public function review(): BelongsTo
    {
        return $this->belongsTo(CompanyReview::class, 'company_review_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
