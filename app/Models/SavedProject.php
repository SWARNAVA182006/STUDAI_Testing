<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedProject extends Model
{
    use HasFactory;

    protected $fillable = [
        'freelancer_id',
        'project_id',
    ];

    // Relationships
    public function freelancer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'freelancer_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(MarketplaceProject::class, 'project_id');
    }
}
