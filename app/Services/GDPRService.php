<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AgentAuditLog;
use App\Models\Application;
use App\Models\InterviewSession;
use App\Models\NegotiationSession;
use App\Models\PaymentTransaction;
use App\Models\Resume;
use App\Models\SkillGap;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * GDPR Service
 *
 * Handles GDPR compliance operations including:
 * - Data export (Right to Access / Data Portability)
 * - Data deletion (Right to Erasure / Right to be Forgotten)
 * - Consent management
 * - Data processing audit trails
 *
 * @see https://gdpr-info.eu/
 */
class GDPRService
{
    /**
     * Data categories for GDPR operations.
     */
    public const CATEGORY_PROFILE = 'profile';
    public const CATEGORY_APPLICATIONS = 'applications';
    public const CATEGORY_INTERVIEWS = 'interviews';
    public const CATEGORY_RESUMES = 'resumes';
    public const CATEGORY_SKILLS = 'skills';
    public const CATEGORY_PAYMENTS = 'payments';
    public const CATEGORY_AGENT = 'agent';
    public const CATEGORY_NEGOTIATIONS = 'negotiations';
    public const CATEGORY_ACTIVITY = 'activity';

    /**
     * Export all user data in a portable format.
     *
     * @param int $userId The user ID to export data for
     * @param array $categories Optional categories to export (exports all if empty)
     * @return array The exported data
     */
    public function exportUserData(int $userId, array $categories = []): array
    {
        $user = User::with(['profile', 'skills'])->findOrFail($userId);

        // If no categories specified, export all
        if (empty($categories)) {
            $categories = [
                self::CATEGORY_PROFILE,
                self::CATEGORY_APPLICATIONS,
                self::CATEGORY_INTERVIEWS,
                self::CATEGORY_RESUMES,
                self::CATEGORY_SKILLS,
                self::CATEGORY_PAYMENTS,
                self::CATEGORY_AGENT,
                self::CATEGORY_NEGOTIATIONS,
                self::CATEGORY_ACTIVITY,
            ];
        }

        $export = [
            'export_date' => now()->toIso8601String(),
            'user_id' => $userId,
            'categories' => [],
        ];

        foreach ($categories as $category) {
            $export['categories'][$category] = $this->exportCategory($user, $category);
        }

        // Log the export for audit trail
        $this->logDataOperation($userId, 'export', $categories);

        return $export;
    }

    /**
     * Export a specific category of user data.
     */
    protected function exportCategory(User $user, string $category): array
    {
        return match ($category) {
            self::CATEGORY_PROFILE => $this->exportProfile($user),
            self::CATEGORY_APPLICATIONS => $this->exportApplications($user),
            self::CATEGORY_INTERVIEWS => $this->exportInterviews($user),
            self::CATEGORY_RESUMES => $this->exportResumes($user),
            self::CATEGORY_SKILLS => $this->exportSkills($user),
            self::CATEGORY_PAYMENTS => $this->exportPayments($user),
            self::CATEGORY_AGENT => $this->exportAgentData($user),
            self::CATEGORY_NEGOTIATIONS => $this->exportNegotiations($user),
            self::CATEGORY_ACTIVITY => $this->exportActivityLogs($user),
            default => [],
        };
    }

    /**
     * Export profile data.
     */
    protected function exportProfile(User $user): array
    {
        $profile = $user->profile;

        return [
            'account' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'created_at' => $user->created_at->toIso8601String(),
                'last_login_at' => $user->last_login_at?->toIso8601String(),
            ],
            'profile' => $profile ? [
                'headline' => $profile->headline,
                'summary' => $profile->summary,
                'phone' => $profile->phone,
                'location' => $profile->location,
                'city' => $profile->city,
                'country' => $profile->country,
                'linkedin_url' => $profile->linkedin_url,
                'github_url' => $profile->github_url,
                'portfolio_url' => $profile->portfolio_url,
                'total_experience_years' => $profile->total_experience_years,
                'current_role' => $profile->current_role,
                'preferred_job_types' => $profile->preferred_job_types,
                'salary_expectation' => $profile->salary_expectation,
                'work_preferences' => $profile->work_preferences,
            ] : null,
            'consents' => $this->exportConsents($user),
        ];
    }

    /**
     * Export consent records.
     */
    protected function exportConsents(User $user): array
    {
        // If you have a consents table, export from there
        // Otherwise, export from user preferences
        return [
            'marketing_emails' => $user->marketing_consent ?? false,
            'data_processing' => $user->data_processing_consent ?? true,
            'third_party_sharing' => $user->third_party_consent ?? false,
            'analytics' => $user->analytics_consent ?? true,
        ];
    }

    /**
     * Export applications.
     */
    protected function exportApplications(User $user): array
    {
        $applications = Application::with(['jobListing.company'])
            ->where('user_id', $user->id)
            ->get();

        return $applications->map(function ($application) {
            return [
                'id' => $application->id,
                'job_title' => $application->jobListing?->title,
                'company' => $application->jobListing?->company?->name,
                'status' => $application->status,
                'cover_letter' => $application->cover_letter,
                'applied_at' => $application->created_at->toIso8601String(),
                'updated_at' => $application->updated_at->toIso8601String(),
            ];
        })->toArray();
    }

    /**
     * Export interview sessions.
     */
    protected function exportInterviews(User $user): array
    {
        $sessions = InterviewSession::with(['questions', 'responses'])
            ->where('user_id', $user->id)
            ->get();

        return $sessions->map(function ($session) {
            return [
                'id' => $session->id,
                'type' => $session->type,
                'status' => $session->status,
                'score' => $session->overall_score,
                'started_at' => $session->created_at->toIso8601String(),
                'completed_at' => $session->completed_at?->toIso8601String(),
                'questions_answered' => $session->responses->count(),
            ];
        })->toArray();
    }

    /**
     * Export resumes.
     */
    protected function exportResumes(User $user): array
    {
        $resumes = Resume::where('user_id', $user->id)->get();

        return $resumes->map(function ($resume) {
            return [
                'id' => $resume->id,
                'title' => $resume->title,
                'is_primary' => $resume->is_primary,
                'content' => $resume->content,
                'skills' => $resume->skills,
                'experience' => $resume->experience,
                'education' => $resume->education,
                'created_at' => $resume->created_at->toIso8601String(),
            ];
        })->toArray();
    }

    /**
     * Export skills and learning data.
     */
    protected function exportSkills(User $user): array
    {
        $skills = $user->skills;
        $gaps = SkillGap::with('learningPath')
            ->where('user_id', $user->id)
            ->get();

        return [
            'skills' => $skills->map(function ($skill) {
                return [
                    'name' => $skill->skill_name,
                    'proficiency' => $skill->proficiency_level,
                    'years_experience' => $skill->years_experience,
                    'validated' => $skill->is_validated,
                ];
            })->toArray(),
            'skill_gaps' => $gaps->map(function ($gap) {
                return [
                    'skill' => $gap->skill_name,
                    'priority' => $gap->priority,
                    'learning_path' => $gap->learningPath?->title,
                    'progress' => $gap->learningPath?->progress ?? 0,
                ];
            })->toArray(),
        ];
    }

    /**
     * Export payment history.
     */
    protected function exportPayments(User $user): array
    {
        $transactions = PaymentTransaction::where('user_id', $user->id)->get();
        $subscriptions = UserSubscription::with('plan')
            ->where('user_id', $user->id)
            ->get();

        return [
            'transactions' => $transactions->map(function ($txn) {
                return [
                    'id' => $txn->id,
                    'amount' => $txn->amount,
                    'currency' => $txn->currency,
                    'status' => $txn->status,
                    'gateway' => $txn->gateway,
                    'date' => $txn->created_at->toIso8601String(),
                ];
            })->toArray(),
            'subscriptions' => $subscriptions->map(function ($sub) {
                return [
                    'plan' => $sub->plan?->name,
                    'status' => $sub->status,
                    'started_at' => $sub->created_at->toIso8601String(),
                    'current_period_ends_at' => $sub->current_period_ends_at?->toIso8601String(),
                ];
            })->toArray(),
        ];
    }

    /**
     * Export agent configuration and data.
     */
    protected function exportAgentData(User $user): array
    {
        $config = $user->agentConfiguration;
        $auditLogs = AgentAuditLog::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(1000)
            ->get();

        return [
            'configuration' => $config ? [
                'is_active' => $config->is_active,
                'auto_apply' => $config->auto_apply,
                'requires_approval' => $config->requires_approval,
                'daily_limit' => $config->daily_limit,
                'min_match_score' => $config->min_match_score,
                'target_roles' => $config->target_roles,
                'preferred_locations' => $config->preferred_locations,
            ] : null,
            'recent_activity' => $auditLogs->map(function ($log) {
                return [
                    'action' => $log->action,
                    'status' => $log->status,
                    'date' => $log->created_at->toIso8601String(),
                ];
            })->toArray(),
        ];
    }

    /**
     * Export negotiation sessions.
     */
    protected function exportNegotiations(User $user): array
    {
        $sessions = NegotiationSession::where('user_id', $user->id)->get();

        return $sessions->map(function ($session) {
            return [
                'id' => $session->id,
                'status' => $session->status,
                'outcome' => $session->outcome,
                'created_at' => $session->created_at->toIso8601String(),
            ];
        })->toArray();
    }

    /**
     * Export activity logs.
     */
    protected function exportActivityLogs(User $user): array
    {
        // Export from audit_logs table if exists
        $logs = DB::table('audit_logs')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(500)
            ->get();

        return $logs->map(function ($log) {
            return [
                'action' => $log->action,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'date' => $log->created_at,
            ];
        })->toArray();
    }

    /**
     * Generate a downloadable export file.
     *
     * @param int $userId The user ID
     * @return string The file path
     */
    public function generateExportFile(int $userId): string
    {
        $data = $this->exportUserData($userId);
        $filename = "gdpr_export_{$userId}_" . now()->format('Y-m-d_His') . '.json';
        $path = "exports/{$filename}";

        Storage::put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $path;
    }

    /**
     * Delete all user data (Right to Erasure).
     *
     * @param int $userId The user ID to delete
     * @param bool $hardDelete Whether to permanently delete (true) or soft delete (false)
     * @return array Summary of deleted data
     */
    public function deleteUserData(int $userId, bool $hardDelete = false): array
    {
        $user = User::findOrFail($userId);
        $summary = [];

        DB::beginTransaction();

        try {
            // Delete in order of dependencies

            // 1. Agent data
            $summary['agent_audit_logs'] = AgentAuditLog::where('user_id', $userId)->delete();

            if ($user->agentConfiguration) {
                $user->agentConfiguration->autoApplications()->delete();
                $user->agentConfiguration->delete();
                $summary['agent_configuration'] = 1;
            }

            // 2. Interview data
            $sessions = InterviewSession::where('user_id', $userId)->get();
            foreach ($sessions as $session) {
                $session->responses()->delete();
                $session->questions()->delete();
            }
            $summary['interview_sessions'] = $sessions->count();
            InterviewSession::where('user_id', $userId)->delete();

            // 3. Negotiation data
            $negotiations = NegotiationSession::where('user_id', $userId)->get();
            foreach ($negotiations as $negotiation) {
                $negotiation->messages()->delete();
            }
            $summary['negotiation_sessions'] = $negotiations->count();
            NegotiationSession::where('user_id', $userId)->delete();

            // 4. Skills and learning
            SkillGap::where('user_id', $userId)->each(function ($gap) {
                $gap->learningPath?->delete();
            });
            $summary['skill_gaps'] = SkillGap::where('user_id', $userId)->delete();
            $summary['user_skills'] = $user->skills()->delete();

            // 5. Applications
            $applications = Application::where('user_id', $userId)->get();
            foreach ($applications as $app) {
                $app->statusHistories()->delete();
                $app->notes()->delete();
            }
            $summary['applications'] = Application::where('user_id', $userId)->delete();

            // 6. Resumes
            $resumes = Resume::where('user_id', $userId)->get();
            foreach ($resumes as $resume) {
                // Delete stored files
                if ($resume->file_path) {
                    Storage::delete($resume->file_path);
                }
            }
            $summary['resumes'] = Resume::where('user_id', $userId)->delete();

            // 7. Payment data (keep for legal/tax compliance, anonymize)
            PaymentTransaction::where('user_id', $userId)->update([
                'user_id' => null,
                'metadata' => json_encode(['anonymized' => true, 'original_user' => $userId]),
            ]);

            // 8. Subscriptions
            $summary['subscriptions'] = UserSubscription::where('user_id', $userId)->delete();

            // 9. Profile
            if ($user->profile) {
                $user->profile->delete();
                $summary['profile'] = 1;
            }

            // 10. Finally, the user
            if ($hardDelete) {
                $user->forceDelete();
            } else {
                $user->delete(); // Soft delete
            }
            $summary['user'] = 1;

            DB::commit();

            // Log the deletion
            $this->logDataOperation($userId, 'delete', array_keys($summary));

            Log::info('GDPR: User data deleted', [
                'user_id' => $userId,
                'hard_delete' => $hardDelete,
                'summary' => $summary,
            ]);

            return $summary;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('GDPR: User data deletion failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Anonymize user data instead of deleting.
     * Useful when data must be retained for legal/analytics purposes.
     *
     * @param int $userId The user ID to anonymize
     * @return array Summary of anonymized data
     */
    public function anonymizeUserData(int $userId): array
    {
        $user = User::findOrFail($userId);
        $anonymizedId = 'anon_' . md5((string) $userId . config('app.key'));

        DB::beginTransaction();

        try {
            // Anonymize user
            $user->update([
                'name' => 'Deleted User',
                'email' => "{$anonymizedId}@deleted.local",
                'password' => bcrypt(str()->random(32)),
            ]);

            // Anonymize profile
            if ($user->profile) {
                $user->profile->update([
                    'phone' => null,
                    'headline' => null,
                    'summary' => null,
                    'linkedin_url' => null,
                    'github_url' => null,
                    'portfolio_url' => null,
                    'photo_url' => null,
                    'address' => null,
                    'city' => null,
                ]);
            }

            // Anonymize applications (keep for employer statistics)
            Application::where('user_id', $userId)->update([
                'cover_letter' => '[Anonymized]',
            ]);

            // Delete resume files but keep records for statistics
            Resume::where('user_id', $userId)->each(function ($resume) {
                if ($resume->file_path) {
                    Storage::delete($resume->file_path);
                }
                $resume->update([
                    'content' => '[Anonymized]',
                    'file_path' => null,
                ]);
            });

            DB::commit();

            $this->logDataOperation($userId, 'anonymize', ['profile', 'applications', 'resumes']);

            return [
                'status' => 'anonymized',
                'anonymized_id' => $anonymizedId,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update user consent preferences.
     *
     * @param int $userId The user ID
     * @param array $consents Array of consent types and their values
     * @return array Updated consent status
     */
    public function updateConsent(int $userId, array $consents): array
    {
        $user = User::findOrFail($userId);

        $allowedConsents = [
            'marketing_emails',
            'data_processing',
            'third_party_sharing',
            'analytics',
            'ai_processing',
        ];

        $updated = [];
        foreach ($consents as $type => $value) {
            if (in_array($type, $allowedConsents)) {
                $field = "{$type}_consent";
                $user->{$field} = (bool) $value;
                $updated[$type] = (bool) $value;
            }
        }

        $user->save();

        // Log consent update
        $this->logDataOperation($userId, 'consent_update', $updated);

        return $updated;
    }

    /**
     * Get user consent status.
     *
     * @param int $userId The user ID
     * @return array Current consent status
     */
    public function getConsentStatus(int $userId): array
    {
        $user = User::findOrFail($userId);

        return [
            'marketing_emails' => $user->marketing_consent ?? false,
            'data_processing' => $user->data_processing_consent ?? true,
            'third_party_sharing' => $user->third_party_consent ?? false,
            'analytics' => $user->analytics_consent ?? true,
            'ai_processing' => $user->ai_processing_consent ?? true,
        ];
    }

    /**
     * Log data operations for audit trail.
     */
    protected function logDataOperation(int $userId, string $operation, array $data): void
    {
        DB::table('gdpr_audit_logs')->insert([
            'user_id' => $userId,
            'operation' => $operation,
            'data' => json_encode($data),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }

    /**
     * Schedule data deletion (for delayed right to erasure).
     *
     * @param int $userId The user ID
     * @param int $delayDays Days to wait before deletion
     * @return array Scheduled deletion info
     */
    public function scheduleDataDeletion(int $userId, int $delayDays = 30): array
    {
        $scheduledAt = now()->addDays($delayDays);

        DB::table('scheduled_deletions')->updateOrInsert(
            ['user_id' => $userId],
            [
                'scheduled_at' => $scheduledAt,
                'status' => 'pending',
                'created_at' => now(),
            ]
        );

        return [
            'user_id' => $userId,
            'scheduled_at' => $scheduledAt->toIso8601String(),
            'can_cancel_until' => $scheduledAt->toIso8601String(),
        ];
    }

    /**
     * Cancel scheduled data deletion.
     *
     * @param int $userId The user ID
     * @return bool Whether cancellation was successful
     */
    public function cancelScheduledDeletion(int $userId): bool
    {
        $deleted = DB::table('scheduled_deletions')
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->delete();

        return $deleted > 0;
    }
}
