<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\GDPRService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * GDPR Controller
 *
 * Handles GDPR compliance endpoints for data export, deletion, and consent management.
 */
/**
 * @OA\Tag(
 *     name="GDPR",
 *     description="GDPR compliance — data export, deletion, consent management"
 * )
 */
class GDPRController extends Controller
{
    public function __construct(
        protected GDPRService $gdprService
    ) {}

    /**
     * Export all user data for download.
     *
     * POST /api/gdpr/export
     *
     * @OA\Post(
     *     path="/api/gdpr/export",
     *     operationId="gdprExport",
     *     tags={"GDPR"},
     *     summary="Export user data (GDPR Art. 20 — Data Portability)",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="categories", type="array",
     *                 @OA\Items(type="string", enum={"profile","applications","interviews","resumes","skills","payments","agent","negotiations","activity"})
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Export file generated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="download_url", type="string", format="url"),
     *             @OA\Property(property="expires_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=500, description="Export generation failed")
     * )
     */
    public function export(Request $request): JsonResponse
    {
        $request->validate([
            'categories' => 'array',
            'categories.*' => 'string|in:profile,applications,interviews,resumes,skills,payments,agent,negotiations,activity',
        ]);

        $userId = $request->user()->id;
        $categories = $request->input('categories', []);

        try {
            $filePath = $this->gdprService->generateExportFile($userId);

            $downloadUrl = Storage::temporaryUrl($filePath, now()->addHours(24));

            return response()->json([
                'message' => 'Data export generated successfully',
                'download_url' => $downloadUrl,
                'expires_at' => now()->addHours(24)->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate data export',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Preview what data will be exported.
     *
     * GET /api/gdpr/export/preview
     */
    public function previewExport(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $data = $this->gdprService->exportUserData($userId);

        // Return summary instead of full data
        $summary = [];
        foreach ($data['categories'] as $category => $items) {
            if (is_array($items)) {
                $summary[$category] = [
                    'records' => is_array($items) && isset($items[0]) ? count($items) : (empty($items) ? 0 : 1),
                ];
            }
        }

        return response()->json([
            'export_date' => $data['export_date'],
            'categories' => $summary,
        ]);
    }

    /**
     * Request account deletion.
     *
     * POST /api/gdpr/delete
     *
     * @OA\Post(
     *     path="/api/gdpr/delete",
     *     operationId="gdprRequestDeletion",
     *     tags={"GDPR"},
     *     summary="Request account deletion (GDPR Art. 17 — Right to Erasure)",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"password","reason"},
     *             @OA\Property(property="password", type="string"),
     *             @OA\Property(property="reason", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Deletion scheduled"),
     *     @OA\Response(response=401, description="Invalid password"),
     *     @OA\Response(response=409, description="Deletion already pending")
     * )
     */
    public function requestDeletion(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|string',
            'confirm' => 'required|accepted',
            'immediate' => 'boolean',
        ]);

        $user = $request->user();

        // Verify password
        if (!\Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'message' => 'Invalid password',
            ], 422);
        }

        $immediate = $request->boolean('immediate', false);

        try {
            if ($immediate) {
                // Immediate deletion
                $summary = $this->gdprService->deleteUserData($user->id, hardDelete: true);

                return response()->json([
                    'message' => 'Account and all associated data have been permanently deleted',
                    'summary' => $summary,
                ]);
            } else {
                // Schedule deletion (30 days grace period)
                $scheduled = $this->gdprService->scheduleDataDeletion($user->id, 30);

                return response()->json([
                    'message' => 'Account deletion scheduled',
                    'scheduled_at' => $scheduled['scheduled_at'],
                    'can_cancel_until' => $scheduled['can_cancel_until'],
                    'info' => 'You can cancel this request within the next 30 days by logging in.',
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to process deletion request',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel scheduled deletion.
     *
     * POST /api/gdpr/delete/cancel
     */
    public function cancelDeletion(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $canceled = $this->gdprService->cancelScheduledDeletion($userId);

        if ($canceled) {
            return response()->json([
                'message' => 'Scheduled deletion has been canceled',
            ]);
        }

        return response()->json([
            'message' => 'No pending deletion request found',
        ], 404);
    }

    /**
     * Request data anonymization instead of deletion.
     *
     * POST /api/gdpr/anonymize
     */
    public function anonymize(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|string',
            'confirm' => 'required|accepted',
        ]);

        $user = $request->user();

        // Verify password
        if (!\Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'message' => 'Invalid password',
            ], 422);
        }

        try {
            $result = $this->gdprService->anonymizeUserData($user->id);

            return response()->json([
                'message' => 'Account has been anonymized',
                'result' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to anonymize account',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get current consent status.
     *
     * GET /api/gdpr/consent
     */
    public function getConsent(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $consents = $this->gdprService->getConsentStatus($userId);

        return response()->json([
            'consents' => $consents,
            'updated_at' => $request->user()->updated_at->toIso8601String(),
        ]);
    }

    /**
     * Update consent preferences.
     *
     * PUT /api/gdpr/consent
     */
    public function updateConsent(Request $request): JsonResponse
    {
        $request->validate([
            'marketing_emails' => 'boolean',
            'data_processing' => 'boolean',
            'third_party_sharing' => 'boolean',
            'analytics' => 'boolean',
            'ai_processing' => 'boolean',
        ]);

        $userId = $request->user()->id;
        $consents = $request->only([
            'marketing_emails',
            'data_processing',
            'third_party_sharing',
            'analytics',
            'ai_processing',
        ]);

        $updated = $this->gdprService->updateConsent($userId, $consents);

        return response()->json([
            'message' => 'Consent preferences updated',
            'consents' => $updated,
        ]);
    }

    /**
     * Get GDPR rights information.
     *
     * GET /api/gdpr/rights
     */
    public function rights(): JsonResponse
    {
        return response()->json([
            'rights' => [
                [
                    'name' => 'Right to Access',
                    'description' => 'You can request a copy of all data we hold about you.',
                    'endpoint' => 'POST /api/gdpr/export',
                ],
                [
                    'name' => 'Right to Rectification',
                    'description' => 'You can update your personal data at any time through your profile settings.',
                    'endpoint' => 'PUT /api/profile',
                ],
                [
                    'name' => 'Right to Erasure',
                    'description' => 'You can request deletion of your account and all associated data.',
                    'endpoint' => 'POST /api/gdpr/delete',
                ],
                [
                    'name' => 'Right to Portability',
                    'description' => 'You can export your data in a machine-readable format (JSON).',
                    'endpoint' => 'POST /api/gdpr/export',
                ],
                [
                    'name' => 'Right to Object',
                    'description' => 'You can opt out of marketing communications and certain data processing.',
                    'endpoint' => 'PUT /api/gdpr/consent',
                ],
                [
                    'name' => 'Right to Restrict Processing',
                    'description' => 'You can request anonymization of your data while keeping a limited record.',
                    'endpoint' => 'POST /api/gdpr/anonymize',
                ],
            ],
            'data_protection_officer' => [
                'email' => config('app.dpo_email', 'dpo@studai.com'),
            ],
            'supervisory_authority' => [
                'name' => 'Data Protection Authority',
                'website' => 'https://www.dataprotection.ie/', // Example - update as needed
            ],
        ]);
    }

    /**
     * Request data processing restriction.
     *
     * POST /api/gdpr/restrict
     */
    public function restrictProcessing(Request $request): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
            'categories' => 'required|array',
            'categories.*' => 'string|in:marketing,analytics,ai_processing,third_party',
        ]);

        $userId = $request->user()->id;
        $categories = $request->input('categories');

        // Update consent for specified categories
        $consents = [];
        if (in_array('marketing', $categories)) {
            $consents['marketing_emails'] = false;
        }
        if (in_array('analytics', $categories)) {
            $consents['analytics'] = false;
        }
        if (in_array('ai_processing', $categories)) {
            $consents['ai_processing'] = false;
        }
        if (in_array('third_party', $categories)) {
            $consents['third_party_sharing'] = false;
        }

        $updated = $this->gdprService->updateConsent($userId, $consents);

        return response()->json([
            'message' => 'Processing restriction applied',
            'restricted_categories' => $categories,
            'current_consents' => $updated,
        ]);
    }
}
