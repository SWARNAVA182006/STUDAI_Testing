<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class PasswordSecurityService
{
    /**
     * Check if password has been compromised using HaveIBeenPwned API.
     */
    public function isPasswordCompromised(string $password): bool
    {
        // SHA-1 hash of the password
        $hash = strtoupper(sha1($password));
        $prefix = substr($hash, 0, 5);
        $suffix = substr($hash, 5);

        try {
            // Query HaveIBeenPwned API
            $response = Http::timeout(5)->get("https://api.pwnedpasswords.com/range/{$prefix}");

            if (!$response->successful()) {
                // If API fails, allow the password (don't block user)
                \Log::warning('HaveIBeenPwned API failed', ['status' => $response->status()]);
                return false;
            }

            // Check if hash suffix is in the response
            $hashes = collect(explode("\n", $response->body()));
            
            return $hashes->contains(function ($line) use ($suffix) {
                return str_starts_with($line, $suffix);
            });

        } catch (\Exception $e) {
            \Log::error('Error checking password breach: ' . $e->getMessage());
            return false; // Fail open
        }
    }

    /**
     * Calculate password strength score (0-100).
     */
    public function calculatePasswordStrength(string $password): int
    {
        $score = 0;
        $length = strlen($password);

        // Length score (max 40 points)
        if ($length >= 12) $score += 40;
        elseif ($length >= 10) $score += 30;
        elseif ($length >= 8) $score += 20;
        else $score += 10;

        // Character variety (max 60 points)
        if (preg_match('/[a-z]/', $password)) $score += 10; // Lowercase
        if (preg_match('/[A-Z]/', $password)) $score += 10; // Uppercase
        if (preg_match('/[0-9]/', $password)) $score += 10; // Numbers
        if (preg_match('/[^a-zA-Z0-9]/', $password)) $score += 20; // Special chars
        
        // Complexity bonus
        if (preg_match('/[a-z].*[A-Z]|[A-Z].*[a-z]/', $password) && 
            preg_match('/[0-9]/', $password) && 
            preg_match('/[^a-zA-Z0-9]/', $password)) {
            $score += 10; // Contains all types
        }

        return min(100, $score);
    }

    /**
     * Validate password meets requirements.
     */
    public function validatePassword(string $password): array
    {
        $errors = [];

        // Minimum length
        if (strlen($password) < 12) {
            $errors[] = 'Password must be at least 12 characters long';
        }

        // Must contain uppercase
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }

        // Must contain lowercase
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }

        // Must contain number
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }

        // Must contain special character
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }

        // Check if compromised
        if ($this->isPasswordCompromised($password)) {
            $errors[] = 'This password has been found in a data breach and cannot be used';
        }

        return $errors;
    }

    /**
     * Check if password was used recently.
     */
    public function isPasswordReused(User $user, string $password): bool
    {
        // Get last 5 password hashes
        $passwordHistories = DB::table('password_histories')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->pluck('password');

        // Check if new password matches any old password
        foreach ($passwordHistories as $oldHash) {
            if (Hash::check($password, $oldHash)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Save password to history.
     */
    public function savePasswordHistory(User $user, string $passwordHash): void
    {
        DB::table('password_histories')->insert([
            'user_id' => $user->id,
            'password' => $passwordHash,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Keep only last 5 passwords
        $keep = DB::table('password_histories')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->pluck('id');

        DB::table('password_histories')
            ->where('user_id', $user->id)
            ->whereNotIn('id', $keep)
            ->delete();
    }

    /**
     * Change user password with validation.
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): array
    {
        // Verify current password
        if (!Hash::check($currentPassword, $user->password)) {
            return ['success' => false, 'errors' => ['Current password is incorrect']];
        }

        // Validate new password
        $errors = $this->validatePassword($newPassword);
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Check password reuse
        if ($this->isPasswordReused($user, $newPassword)) {
            return ['success' => false, 'errors' => ['Cannot reuse recent passwords']];
        }

        // Update password
        $user->password = Hash::make($newPassword);
        $user->save();

        // Save to history
        $this->savePasswordHistory($user, $user->password);

        // Log event
        app(AuditService::class)->logPasswordChange($user);

        return ['success' => true, 'errors' => []];
    }

    /**
     * Get password requirements as array.
     */
    public function getPasswordRequirements(): array
    {
        return [
            'min_length' => 12,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_numbers' => true,
            'require_special' => true,
            'check_breach' => true,
            'prevent_reuse' => true,
            'reuse_limit' => 5,
        ];
    }
}
