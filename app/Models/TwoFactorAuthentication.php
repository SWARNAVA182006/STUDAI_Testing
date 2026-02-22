<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorAuthentication extends Model
{
    protected $fillable = [
        'user_id',
        'secret',
        'recovery_codes',
        'enabled',
        'enabled_at',
    ];

    protected $casts = [
        'recovery_codes' => 'array',
        'enabled' => 'boolean',
        'enabled_at' => 'datetime',
    ];

    protected $hidden = [
        'secret',
        'recovery_codes',
    ];

    /**
     * Get the user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate a new secret key.
     */
    public function generateSecret(): string
    {
        $google2fa = new Google2FA();
        $this->secret = $google2fa->generateSecretKey();
        $this->save();
        
        return $this->secret;
    }

    /**
     * Generate recovery codes.
     */
    public function generateRecoveryCodes(): array
    {
        $codes = [];
        
        for ($i = 0; $i < 10; $i++) {
            $codes[] = strtoupper(substr(bin2hex(random_bytes(5)), 0, 10));
        }
        
        $this->recovery_codes = array_map(function ($code) {
            return ['code' => $code, 'used' => false];
        }, $codes);
        
        $this->save();
        
        return $codes;
    }

    /**
     * Verify TOTP code.
     */
    public function verifyCode(string $code): bool
    {
        if (!$this->secret) {
            return false;
        }

        $google2fa = new Google2FA();
        return $google2fa->verifyKey($this->secret, $code);
    }

    /**
     * Verify recovery code.
     */
    public function verifyRecoveryCode(string $code): bool
    {
        if (!$this->recovery_codes) {
            return false;
        }

        foreach ($this->recovery_codes as $index => $recoveryCode) {
            if (strtoupper($code) === $recoveryCode['code'] && !$recoveryCode['used']) {
                // Mark as used
                $codes = $this->recovery_codes;
                $codes[$index]['used'] = true;
                $this->recovery_codes = $codes;
                $this->save();
                
                return true;
            }
        }

        return false;
    }

    /**
     * Enable 2FA.
     */
    public function enable(): void
    {
        $this->enabled = true;
        $this->enabled_at = now();
        $this->save();
    }

    /**
     * Disable 2FA.
     */
    public function disable(): void
    {
        $this->enabled = false;
        $this->enabled_at = null;
        $this->save();
    }

    /**
     * Get QR code URL for Google Authenticator.
     */
    public function getQrCodeUrl(string $email): string
    {
        $google2fa = new Google2FA();
        $companyName = config('app.name');
        
        return $google2fa->getQRCodeUrl(
            $companyName,
            $email,
            $this->secret
        );
    }
}
