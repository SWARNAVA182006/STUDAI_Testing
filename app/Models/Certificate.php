<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    protected $fillable = [
        'user_id',
        'assessment_id',
        'attempt_id',
        'certificate_number',
        'verification_code',
        'score',
        'issued_at',
        'expires_at',
        'is_verified',
    ];
    
    protected $casts = [
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_verified' => 'boolean',
    ];
    
    /**
     * Get the user who earned this certificate
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Get the assessment this certificate is for
     */
    public function assessment()
    {
        return $this->belongsTo(Assessment::class);
    }
    
    /**
     * Get the attempt that earned this certificate
     */
    public function attempt()
    {
        return $this->belongsTo(AssessmentAttempt::class, 'attempt_id');
    }
    
    /**
     * Get any badges earned with this certificate
     */
    public function badges()
    {
        return $this->hasMany(BadgeUser::class, 'certificate_id');
    }
    
    /**
     * Generate unique certificate number
     */
    public static function generateCertificateNumber(): string
    {
        $date = now()->format('Ymd');
        $count = self::whereDate('created_at', today())->count() + 1;
        return sprintf('CERT-%s-%04d', $date, $count);
    }
    
    /**
     * Generate random verification code
     */
    public static function generateVerificationCode(): string
    {
        return strtoupper(bin2hex(random_bytes(8))); // 16 character hex string
    }
    
    /**
     * Verify certificate by verification code
     */
    public static function verify(string $code): ?self
    {
        return self::where('verification_code', strtoupper($code))
            ->where('is_verified', true)
            ->first();
    }
    
    /**
     * Check if certificate has expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && now()->greaterThan($this->expires_at);
    }
    
    /**
     * Get certificate status
     */
    public function getStatusAttribute(): string
    {
        if (!$this->is_verified) return 'revoked';
        if ($this->isExpired()) return 'expired';
        return 'valid';
    }
    
    /**
     * Get grade based on score
     */
    public function getGradeAttribute(): string
    {
        return match(true) {
            $this->score >= 95 => 'A+',
            $this->score >= 90 => 'A',
            $this->score >= 85 => 'A-',
            $this->score >= 80 => 'B+',
            $this->score >= 75 => 'B',
            $this->score >= 70 => 'B-',
            default => 'C',
        };
    }
    
    /**
     * Get shareable URL for LinkedIn
     */
    public function getLinkedInShareUrlAttribute(): string
    {
        $params = http_build_query([
            'name' => $this->assessment->title . ' Certificate',
            'organizationName' => 'StudAI Career Platform',
            'issueYear' => $this->issued_at->year,
            'issueMonth' => $this->issued_at->month,
            'certUrl' => route('certificates.verify', $this->verification_code),
            'certId' => $this->certificate_number,
        ]);
        
        return 'https://www.linkedin.com/profile/add?' . $params;
    }
    
    /**
     * Get download URL for PDF certificate
     */
    public function getDownloadUrlAttribute(): string
    {
        return route('certificates.download', $this->id);
    }
    
    /**
     * Get verification URL
     */
    public function getVerificationUrlAttribute(): string
    {
        return route('certificates.verify', $this->verification_code);
    }
}
