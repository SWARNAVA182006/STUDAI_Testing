<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResumeVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'resume_id',
        'version_number',
        'resume_data',
        'change_description',
        'created_by',
    ];

    protected $casts = [
        'resume_data' => 'array',
        'version_number' => 'integer',
    ];

    /**
     * Relationships
     */
    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Restore this version
     */
    public function restore(): void
    {
        $resume = $this->resume;
        
        // Create a new version before restoring
        $resume->createVersion("Restored from version {$this->version_number}");
        
        // Update resume with this version's data
        $resume->update($this->resume_data);
    }
}
