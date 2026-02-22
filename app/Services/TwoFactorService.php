<?php

namespace App\Services;

use App\Models\User;
use App\Models\TwoFactorAuthentication;
use Illuminate\Support\Facades\Crypt;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TwoFactorService
{
    /**
     * Enable 2FA for a user.
     */
    public function enable(User $user): array
    {
        $twoFactor = $user->twoFactorAuth ?? new TwoFactorAuthentication(['user_id' => $user->id]);
        
        // Generate secret if not exists
        if (!$twoFactor->secret) {
            $twoFactor->generateSecret();
        }

        // Generate recovery codes
        $recoveryCodes = $twoFactor->generateRecoveryCodes();

        // Get QR code
        $qrCodeUrl = $twoFactor->getQrCodeUrl($user->email);
        $qrCodeSvg = QrCode::size(200)->generate($qrCodeUrl);

        return [
            'secret' => $twoFactor->secret,
            'qr_code' => $qrCodeSvg,
            'recovery_codes' => $recoveryCodes,
        ];
    }

    /**
     * Confirm and activate 2FA.
     */
    public function confirm(User $user, string $code): bool
    {
        $twoFactor = $user->twoFactorAuth;
        
        if (!$twoFactor) {
            return false;
        }

        // Verify the code
        if (!$twoFactor->verifyCode($code)) {
            return false;
        }

        // Enable 2FA
        $twoFactor->enable();

        // Log event
        app(AuditService::class)->log2FAEnabled($user);

        return true;
    }

    /**
     * Disable 2FA for a user.
     */
    public function disable(User $user, string $password): bool
    {
        // Verify password
        if (!auth()->validate(['email' => $user->email, 'password' => $password])) {
            return false;
        }

        $twoFactor = $user->twoFactorAuth;
        
        if (!$twoFactor) {
            return true; // Already disabled
        }

        $twoFactor->disable();

        // Log event
        app(AuditService::class)->log2FADisabled($user);

        return true;
    }

    /**
     * Verify 2FA code during login.
     */
    public function verify(User $user, string $code): bool
    {
        $twoFactor = $user->twoFactorAuth;
        
        if (!$twoFactor || !$twoFactor->enabled) {
            return true; // 2FA not enabled
        }

        // Try TOTP code first
        if ($twoFactor->verifyCode($code)) {
            return true;
        }

        // Try recovery code
        return $twoFactor->verifyRecoveryCode($code);
    }

    /**
     * Generate new recovery codes.
     */
    public function regenerateRecoveryCodes(User $user): array
    {
        $twoFactor = $user->twoFactorAuth;
        
        if (!$twoFactor) {
            throw new \Exception('2FA is not enabled');
        }

        return $twoFactor->generateRecoveryCodes();
    }

    /**
     * Check if user has 2FA enabled.
     */
    public function isEnabled(User $user): bool
    {
        return $user->twoFactorAuth?->enabled ?? false;
    }

    /**
     * Get remaining recovery codes count.
     */
    public function getRemainingRecoveryCodes(User $user): int
    {
        $twoFactor = $user->twoFactorAuth;
        
        if (!$twoFactor || !$twoFactor->recovery_codes) {
            return 0;
        }

        return collect($twoFactor->recovery_codes)
            ->filter(fn($code) => !$code['used'])
            ->count();
    }
}
