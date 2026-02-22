<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Newsletter extends Model
{
    protected $fillable = [
        'email',
        'token',
        'ip_address',
        'is_subscribed',
        'subscribed_at',
        'unsubscribed_at',
    ];

    protected $casts = [
        'is_subscribed' => 'boolean',
        'subscribed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($newsletter) {
            if (!$newsletter->token) {
                $newsletter->token = Str::random(32);
            }
            if (!$newsletter->subscribed_at) {
                $newsletter->subscribed_at = now();
            }
        });
    }

    public function scopeSubscribed($query)
    {
        return $query->where('is_subscribed', true);
    }
}
