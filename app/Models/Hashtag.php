<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hashtag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'posts_count',
        'is_trending',
    ];

    protected $casts = [
        'is_trending' => 'boolean',
    ];

    public function posts(): HasMany
    {
        return $this->belongsToMany(UserPost::class, 'post_hashtag', 'hashtag_id', 'post_id');
    }

    public function scopeTrending($query)
    {
        return $query->where('is_trending', true)->orderByDesc('posts_count');
    }

    /**
     * Update trending status based on recent activity
     */
    public static function updateTrendingStatus(): void
    {
        // Reset all trending
        self::query()->update(['is_trending' => false]);
        
        // Mark top hashtags as trending (used in last 24 hours)
        $trendingIds = \DB::table('post_hashtag')
            ->join('user_posts', 'post_hashtag.post_id', '=', 'user_posts.id')
            ->where('user_posts.created_at', '>=', now()->subDay())
            ->select('post_hashtag.hashtag_id')
            ->groupBy('post_hashtag.hashtag_id')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(20)
            ->pluck('hashtag_id');
        
        self::whereIn('id', $trendingIds)->update(['is_trending' => true]);
    }
}
