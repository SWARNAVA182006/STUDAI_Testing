<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class AuditService
{
    /**
     * Log an audit event.
     */
    public function log(
        string $event,
        $auditable = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $tags = null
    ): AuditLog {
        $user = Auth::user();
        $request = request();

        return AuditLog::create([
            'user_id' => $user?->id,
            'user_type' => $user?->account_type,
            'event' => $event,
            'auditable_type' => $auditable ? get_class($auditable) : null,
            'auditable_id' => $auditable?->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'tags' => $tags,
        ]);
    }

    /**
     * Log user login.
     */
    public function logLogin($user): void
    {
        $this->log('user.login', $user, null, null, 'security,authentication');
    }

    /**
     * Log user logout.
     */
    public function logLogout($user): void
    {
        $this->log('user.logout', $user, null, null, 'security,authentication');
    }

    /**
     * Log failed login attempt.
     */
    public function logFailedLogin(string $email): void
    {
        AuditLog::create([
            'user_id' => null,
            'user_type' => null,
            'event' => 'user.login.failed',
            'auditable_type' => null,
            'auditable_id' => null,
            'old_values' => null,
            'new_values' => ['email' => $email],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'tags' => 'security,authentication,failed',
        ]);
    }

    /**
     * Log payment transaction.
     */
    public function logPayment($transaction, string $event): void
    {
        $this->log(
            $event,
            $transaction,
            null,
            [
                'amount' => $transaction->amount,
                'gateway' => $transaction->payment_gateway,
                'status' => $transaction->status,
            ],
            'payment,financial'
        );
    }

    /**
     * Log user deletion.
     */
    public function logUserDeletion($user): void
    {
        $this->log(
            'user.deleted',
            $user,
            $user->toArray(),
            null,
            'security,user-management,critical'
        );
    }

    /**
     * Log role change.
     */
    public function logRoleChange($user, string $oldRole, string $newRole): void
    {
        $this->log(
            'user.role.changed',
            $user,
            ['role' => $oldRole],
            ['role' => $newRole],
            'security,user-management,critical'
        );
    }

    /**
     * Log API token generation.
     */
    public function logApiTokenGeneration($token): void
    {
        $this->log(
            'api.token.generated',
            $token,
            null,
            [
                'name' => $token->name,
                'abilities' => $token->abilities,
            ],
            'security,api,token'
        );
    }

    /**
     * Log API token revocation.
     */
    public function logApiTokenRevocation($token): void
    {
        $this->log(
            'api.token.revoked',
            $token,
            ['status' => 'active'],
            ['status' => 'revoked'],
            'security,api,token'
        );
    }

    /**
     * Log webhook configuration change.
     */
    public function logWebhookChange($webhook, string $event): void
    {
        $this->log(
            $event,
            $webhook,
            null,
            [
                'url' => $webhook->url,
                'events' => $webhook->events,
            ],
            'security,webhook,api'
        );
    }

    /**
     * Log sensitive data access.
     */
    public function logDataAccess(string $dataType, $record): void
    {
        $this->log(
            'data.accessed',
            $record,
            null,
            ['data_type' => $dataType],
            'security,data-access,privacy'
        );
    }

    /**
     * Log password change.
     */
    public function logPasswordChange($user): void
    {
        $this->log(
            'user.password.changed',
            $user,
            null,
            null,
            'security,authentication,critical'
        );
    }

    /**
     * Log 2FA events.
     */
    public function log2FAEnabled($user): void
    {
        $this->log(
            'user.2fa.enabled',
            $user,
            null,
            null,
            'security,authentication,2fa'
        );
    }

    public function log2FADisabled($user): void
    {
        $this->log(
            'user.2fa.disabled',
            $user,
            null,
            null,
            'security,authentication,2fa'
        );
    }

    /**
     * Get security events for a user.
     */
    public function getSecurityEvents(int $userId, int $days = 30)
    {
        return AuditLog::byUser($userId)
            ->security()
            ->recent($days)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get recent suspicious activity.
     */
    public function getSuspiciousActivity(int $hours = 24)
    {
        return AuditLog::where('created_at', '>=', now()->subHours($hours))
            ->where(function ($query) {
                $query->where('tags', 'like', '%failed%')
                      ->orWhere('event', 'like', '%.failed');
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('ip_address');
    }

    /**
     * Clean old audit logs.
     */
    public function cleanOldLogs(): int
    {
        $retentionDays = config('audit.retention_days', 90);
        
        // Keep critical events for 1 year
        $criticalRetention = now()->subYear();
        $normalRetention = now()->subDays($retentionDays);

        // Delete old non-critical logs
        $deleted = AuditLog::where('created_at', '<', $normalRetention)
            ->where('tags', 'not like', '%critical%')
            ->delete();

        // Delete old critical logs
        $deleted += AuditLog::where('created_at', '<', $criticalRetention)
            ->delete();

        return $deleted;
    }
}
