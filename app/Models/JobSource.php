<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'url',
        'scraping_config',
        'is_active',
        'priority',
        'success_rate',
        'last_scraped_at',
        'jobs_found_today',
        'jobs_found_total',
    ];

    protected $casts = [
        'scraping_config' => 'array',
        'is_active' => 'boolean',
        'priority' => 'integer',
        'success_rate' => 'integer',
        'last_scraped_at' => 'datetime',
        'jobs_found_today' => 'integer',
        'jobs_found_total' => 'integer',
    ];

    public function discoveredJobs()
    {
        return $this->hasMany(DiscoveredJob::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeHighPriority($query)
    {
        return $query->where('priority', '>=', 7)->orderByDesc('priority');
    }

    public function incrementJobsFound(): void
    {
        // Reset daily counter if new day
        if ($this->last_scraped_at && $this->last_scraped_at->isToday()) {
            $this->increment('jobs_found_today');
        } else {
            $this->jobs_found_today = 1;
        }

        $this->increment('jobs_found_total');
        $this->last_scraped_at = now();
        $this->save();
    }

    public function updateSuccessRate(bool $success): void
    {
        // Simple moving average
        $this->success_rate = ($this->success_rate * 0.9) + ($success ? 10 : 0);
        $this->save();
    }
}
