<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyBlacklist extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_name',
        'reason',
        'notes',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function isBlacklisted(int $userId, string $companyName): bool
    {
        return static::where('user_id', $userId)
            ->where('company_name', 'like', "%{$companyName}%")
            ->exists();
    }

    public static function add(int $userId, string $companyName, ?string $reason = null, ?string $notes = null): void
    {
        static::create([
            'user_id' => $userId,
            'company_name' => $companyName,
            'reason' => $reason,
            'notes' => $notes,
        ]);
    }
}
